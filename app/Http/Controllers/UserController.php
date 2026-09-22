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
        $departementId = $request->integer('departement') ?: null;
        $users = User::query()
            ->with('departement')
            ->when($departementId, fn ($query) => $query->where('departement_id', $departementId))
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        $departements = Departement::query()->withCount('users')->orderBy('nom')->get();

        return view('users.index', compact('users', 'departements', 'departementId'));
    }

    public function show(User $user)
    {
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
        return [
            'roles' => User::roleOptions(),
            'departements' => Departement::query()->orderBy('nom')->get(),
        ];
    }
}
