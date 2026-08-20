<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

class AccessService
{
    /**
     * Default permission keys granted to each system role slug.
     *
     * @return array<string, array<int, string>>
     */
    public static function defaultRolePermissions(): array
    {
        $all = collect(Permission::catalog())->pluck('key')->all();

        $comm = [
            'dashboard.view',
            'statistiques.view',
            'gestion_projet.view', 'gestion_projet.create', 'gestion_projet.update',
            'campagnes.view', 'campagnes.create', 'campagnes.update',
            'calendrier_editorial.view', 'calendrier_editorial.create', 'calendrier_editorial.update',
            'evenements.view', 'evenements.create', 'evenements.update',
            'stocks.view', 'stocks_mouvements.view',
        ];

        $budget = [
            'dashboard.view',
            'statistiques.view',
            'budget_annuel.view', 'budget_annuel.create', 'budget_annuel.update',
            'budget_mensuel.view', 'budget_mensuel.create', 'budget_mensuel.update',
            'depenses.view', 'depenses.create', 'depenses.update',
            'fournisseurs.view', 'fournisseurs.create', 'fournisseurs.update',
            'campagnes.view',
        ];

        $admin = array_values(array_diff($all, ['acces.manage']));

        $stagiaire = [
            'dashboard.view',
            'gestion_projet.view', 'gestion_projet.create', 'gestion_projet.update',
            'calendrier_editorial.view', 'calendrier_editorial.create',
            'evenements.view',
            'stocks.view', 'stocks_mouvements.view',
        ];

        return [
            'super_admin' => $all,
            'administrateur' => $admin,
            'responsable_communication' => $comm,
            'gestionnaire_budget' => $budget,
            'stagiaire' => $stagiaire,
        ];
    }

    public static function bootstrap(): void
    {
        Permission::syncCatalog();

        $map = static::defaultRolePermissions();
        $permissions = Permission::pluck('id', 'key');

        foreach ($map as $slug => $keys) {
            $role = Role::where('slug', $slug)->first();
            if (! $role) {
                continue;
            }
            // Only seed if role has no permissions yet
            if ($role->permissions()->exists()) {
                continue;
            }
            $ids = collect($keys)->map(fn ($k) => $permissions[$k] ?? null)->filter()->values()->all();
            $role->permissions()->sync($ids);
        }

        // Legacy users without direct permissions inherit their role matrix
        User::query()->whereDoesntHave('permissions')->each(function (User $user) {
            static::syncUserPermissionsFromRole($user);
        });
    }

    public static function syncUserPermissionsFromRole(User $user): void
    {
        $role = Role::where('slug', $user->role)->first();
        if (! $role) {
            return;
        }

        $ids = $role->permissions()->pluck('permissions.id')->all();
        $user->permissions()->sync($ids);
    }
}
