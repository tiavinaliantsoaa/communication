<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AccessService;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AccesController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless($request->user()?->isSuperAdmin(), 403);

            return $next($request);
        });
    }

    public function index()
    {
        AccessService::bootstrap();

        $roles = Role::withCount('permissions')->orderByDesc('is_system')->orderBy('name')->get();
        $users = User::orderBy('name')->get(['id', 'name', 'email', 'role', 'avatar_path']);
        $permissions = Permission::orderBy('position')->get()->groupBy('group');

        return view('acces.index', [
            'title' => 'Gestion d’accès',
            'subtitle' => 'Rôles et permissions par utilisateur',
            'roles' => $roles,
            'users' => $users,
            'permissionGroups' => $permissions,
            'homePages' => Role::homePageOptions(),
        ]);
    }

    public function storeRole(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'home_route' => ['nullable', 'string', Rule::in(array_keys(Role::homePageOptions()))],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,key'],
        ]);

        $role = Role::create([
            'name' => $data['name'],
            'slug' => Role::makeSlug($data['name']),
            'description' => $data['description'] ?? null,
            'home_route' => $data['home_route'] ?? null,
            'is_system' => false,
        ]);

        $ids = Permission::whereIn('key', $data['permissions'] ?? [])->pluck('id');
        $role->permissions()->sync($ids);

        app(ActivityLogger::class)->log(
            'acces',
            auth()->user()->name.' a créé le rôle « '.$role->name.' »',
            auth()->user(),
            'create',
            'Gestion d’accès',
            route('acces.index'),
            $role
        );

        return back()->with('success', 'Rôle créé.');
    }

    public function updateRole(Request $request, Role $role)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'home_route' => ['nullable', 'string', Rule::in(array_keys(Role::homePageOptions()))],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,key'],
        ]);

        $role->name = $data['name'];
        if (! $role->is_system) {
            // keep slug stable for system; custom roles can keep slug
        }
        $role->description = $data['description'] ?? null;
        $role->home_route = $data['home_route'] ?: null;
        $role->save();

        $ids = Permission::whereIn('key', $data['permissions'] ?? [])->pluck('id');
        $role->permissions()->sync($ids);

        // Propagate to users with this role who haven't been customized? 
        // Spec: checkboxes per user — we sync all users of this role to the new role permissions
        // only when requested via flag, or always for role update as baseline.
        User::where('role', $role->slug)->each(function (User $user) {
            AccessService::syncUserPermissionsFromRole($user);
        });

        app(ActivityLogger::class)->log(
            'acces',
            auth()->user()->name.' a modifié le rôle « '.$role->name.' »',
            auth()->user(),
            'update',
            'Gestion d’accès',
            route('acces.index'),
            $role
        );

        return back()->with('success', 'Rôle mis à jour. Les utilisateurs de ce rôle ont hérité des permissions.');
    }

    public function destroyRole(Role $role)
    {
        if ($role->is_system) {
            return back()->with('error', 'Impossible de supprimer un rôle système.');
        }

        if (User::where('role', $role->slug)->exists()) {
            return back()->with('error', 'Des utilisateurs utilisent encore ce rôle.');
        }

        $name = $role->name;
        $role->delete();

        app(ActivityLogger::class)->log(
            'acces',
            auth()->user()->name.' a supprimé le rôle « '.$name.' »',
            auth()->user(),
            'delete',
            'Gestion d’accès',
            route('acces.index')
        );

        return back()->with('success', 'Rôle supprimé.');
    }

    public function editUser(User $user)
    {
        AccessService::bootstrap();

        // Ensure user has permissions (migrate legacy users)
        if (! $user->permissions()->exists()) {
            AccessService::syncUserPermissionsFromRole($user);
        }

        $permissionGroups = Permission::orderBy('position')->get()->groupBy('group');
        $userKeys = $user->permissions()->pluck('key')->all();
        $roles = Role::with('permissions')->orderBy('name')->get();

        return view('acces.user', [
            'title' => 'Accès — '.$user->name,
            'subtitle' => 'Cocher les pages et actions autorisées',
            'user' => $user,
            'permissionGroups' => $permissionGroups,
            'userKeys' => $userKeys,
            'roles' => $roles,
        ]);
    }

    public function updateUser(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => ['required', 'string', Rule::exists('roles', 'slug')],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,key'],
        ]);

        // Prevent locking yourself out of access management
        if ((int) $user->id === (int) $request->user()->id && $data['role'] !== 'super_admin') {
            return back()->with('error', 'Vous ne pouvez pas retirer votre propre rôle Super Admin.');
        }

        DB::transaction(function () use ($user, $data) {
            $user->role = $data['role'];
            $user->save();

            $ids = Permission::whereIn('key', $data['permissions'] ?? [])->pluck('id');
            $user->permissions()->sync($ids);
        });

        app(ActivityLogger::class)->log(
            'acces',
            auth()->user()->name.' a mis à jour les accès de « '.$user->name.' »',
            auth()->user(),
            'update',
            'Gestion d’accès',
            route('acces.users.edit', $user),
            $user
        );

        return redirect()->route('acces.index')->with('success', 'Accès utilisateur enregistrés.');
    }

    public function editRole(Role $role)
    {
        AccessService::bootstrap();

        $permissionGroups = Permission::orderBy('position')->get()->groupBy('group');
        $roleKeys = $role->permissions()->pluck('key')->all();

        return view('acces.role', [
            'title' => 'Rôle — '.$role->name,
            'subtitle' => 'Permissions du rôle',
            'role' => $role,
            'permissionGroups' => $permissionGroups,
            'roleKeys' => $roleKeys,
            'homePages' => Role::homePageOptions(),
        ]);
    }
}
