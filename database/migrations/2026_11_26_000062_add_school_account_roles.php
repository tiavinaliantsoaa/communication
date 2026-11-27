<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $now = now();
        $roles = [
            ['name' => 'Étudiant', 'slug' => 'etudiant', 'description' => 'Compte étudiant de la gestion école', 'home_route' => 'ecole.dashboard'],
            ['name' => 'Parent', 'slug' => 'parent', 'description' => 'Compte parent de la gestion école', 'home_route' => 'ecole.dashboard'],
            ['name' => 'Enseignant', 'slug' => 'enseignant', 'description' => 'Compte enseignant de la gestion école', 'home_route' => 'ecole.dashboard'],
            ['name' => 'Direction', 'slug' => 'direction', 'description' => 'Consultation des sections de l’application', 'home_route' => 'dashboard'],
        ];

        foreach ($roles as $role) {
            $exists = DB::table('roles')->where('slug', $role['slug'])->exists();
            if ($exists) {
                continue;
            }

            DB::table('roles')->insert([
                ...$role,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (! Schema::hasTable('permissions') || ! Schema::hasTable('permission_role')) {
            return;
        }

        $permissionId = DB::table('permissions')->where('key', 'ecole.view')->value('id');
        if ($permissionId) {
            foreach (['etudiant', 'parent', 'enseignant'] as $slug) {
                $roleId = DB::table('roles')->where('slug', $slug)->value('id');
                if (! $roleId) {
                    continue;
                }
                $linked = DB::table('permission_role')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permissionId)
                    ->exists();
                if ($linked) {
                    continue;
                }
                DB::table('permission_role')->insert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }

        $directionId = DB::table('roles')->where('slug', 'direction')->value('id');
        if ($directionId && DB::table('permission_role')->where('role_id', $directionId)->doesntExist()) {
            $viewIds = DB::table('permissions')->where('key', 'like', '%.view')->pluck('id');
            foreach ($viewIds as $viewId) {
                DB::table('permission_role')->insert([
                    'role_id' => $directionId,
                    'permission_id' => $viewId,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $ids = DB::table('roles')->whereIn('slug', ['etudiant', 'parent', 'enseignant', 'direction'])->pluck('id');
        if ($ids->isEmpty()) {
            return;
        }

        if (Schema::hasTable('permission_role')) {
            DB::table('permission_role')->whereIn('role_id', $ids)->delete();
        }

        DB::table('roles')->whereIn('slug', ['etudiant', 'parent', 'enseignant', 'direction'])->delete();
    }
};
