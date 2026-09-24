<?php

namespace App\Http\Controllers;

use App\Models\Departement;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $actor = $request->user();
        $departementId = $actor->isSuperAdmin()
            ? ($request->integer('departement') ?: null)
            : $actor->currentDepartementId();
        $users = User::query()
            ->with('departement')
            ->when($departementId, fn ($query) => $query->where('departement_id', $departementId))
            ->when(! $actor->isSuperAdmin(), fn ($query) => $query->where('role', '!=', 'super_admin'))
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        $departements = Departement::query()
            ->withCount('users')
            ->when(! $actor->isSuperAdmin(), fn ($query) => $query->whereKey($actor->currentDepartementId()))
            ->orderBy('nom')
            ->get();

        return view('users.index', compact('users', 'departements', 'departementId'));
    }

    public function show(User $user)
    {
        $this->assertCanManage($user);
        $user->load('departement');

        return view('users.show', compact('user'));
    }

    public function create()
    {
        return view('users.create', $this->formLookups());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(array_keys(User::roleOptions()))],
            'departement_id' => ['required', 'exists:departements,id'],
        ], [
            'departement_id.required' => 'Assignez un département à cet utilisateur.',
        ]);

        $this->assertRoleAllowed($validated['role']);
        $this->assertDepartementAllowed((int) $validated['departement_id']);

        $created = User::create($validated);
        \App\Services\AccessService::syncUserPermissionsFromRole($created);

        app(ActivityLogger::class)->log(
            'user',
            auth()->user()->name.' a créé l\'utilisateur « '.$created->name.' »',
            auth()->user(),
            'create',
            'Utilisateurs',
            route('users.index'),
            $created
        );

        return redirect()->route('users.index')->with('success', 'Utilisateur créé avec succès.');
    }

    public function edit(User $user)
    {
        $this->assertCanManage($user);

        return view('users.edit', array_merge(['user' => $user], $this->formLookups()));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(array_keys(User::roleOptions()))],
            'departement_id' => ['required', 'exists:departements,id'],
        ], [
            'departement_id.required' => 'Assignez un département à cet utilisateur.',
        ]);

        $this->assertCanManage($user);
        $this->assertRoleAllowed($validated['role'], $user);
        $this->assertDepartementAllowed((int) $validated['departement_id']);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $roleChanged = ($validated['role'] ?? null) !== $user->role;
        $user->update($validated);

        if ($roleChanged) {
            \App\Services\AccessService::syncUserPermissionsFromRole($user->fresh());
        }

        app(ActivityLogger::class)->log(
            'user',
            auth()->user()->name.' a modifié l\'utilisateur « '.$user->name.' »',
            auth()->user(),
            'update',
            'Utilisateurs',
            route('users.index'),
            $user
        );

        return redirect()->route('users.index')->with('success', 'Utilisateur mis à jour avec succès.');
    }

    public function destroy(User $user)
    {
        $this->assertCanManage($user);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $name = $user->name;
        $user->delete();

        app(ActivityLogger::class)->log(
            'user',
            auth()->user()->name.' a supprimé l\'utilisateur « '.$name.' »',
            auth()->user(),
            'delete',
            'Utilisateurs',
            route('users.index')
        );

        return redirect()->route('users.index')->with('success', 'Utilisateur supprimé avec succès.');
    }

    private function formLookups(): array
    {
        $actor = auth()->user();
        $roles = User::roleOptions();
        if (! $actor?->isSuperAdmin()) {
            unset($roles['super_admin']);
        }

        $departements = Departement::query()->orderBy('nom');
        if (! $actor?->isSuperAdmin()) {
            $departements->whereKey($actor->currentDepartementId());
        }

        return [
            'roles' => $roles,
            'departements' => $departements->get(),
        ];
    }

    private function assertCanManage(User $user): void
    {
        $actor = auth()->user();
        if (! $actor || $actor->isSuperAdmin()) {
            return;
        }

        abort_if($user->isSuperAdmin(), 403);
        abort_unless((int) $user->departement_id === (int) $actor->currentDepartementId(), 403);
    }

    private function assertRoleAllowed(string $role, ?User $target = null): void
    {
        $actor = auth()->user();
        if (! $actor || $actor->isSuperAdmin()) {
            return;
        }

        abort_if($role === 'super_admin', 403, 'Seul un super admin peut attribuer ce rôle.');
        abort_if($target && (int) $target->id === (int) $actor->id && $role !== $actor->role, 403, 'Vous ne pouvez pas changer votre propre rôle.');
    }

    private function assertDepartementAllowed(int $departementId): void
    {
        $actor = auth()->user();
        if (! $actor || $actor->isSuperAdmin()) {
            return;
        }

        abort_unless($departementId === (int) $actor->currentDepartementId(), 403);
    }
}
