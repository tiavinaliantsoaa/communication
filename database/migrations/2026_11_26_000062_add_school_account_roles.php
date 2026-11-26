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
            ['name' => 'Étudiant', 'slug' => 'etudiant', 'description' => 'Compte étudiant de la gestion école'],
            ['name' => 'Parent', 'slug' => 'parent', 'description' => 'Compte parent de la gestion école'],
            ['name' => 'Enseignant', 'slug' => 'enseignant', 'description' => 'Compte enseignant de la gestion école'],
        ];

        foreach ($roles as $role) {
            $exists = DB::table('roles')->where('slug', $role['slug'])->exists();
            if ($exists) {
                continue;
            }

            DB::table('roles')->insert([
                ...$role,
                'home_route' => 'ecole.dashboard',
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (! Schema::hasTable('permissions') || ! Schema::hasTable('permission_role')) {
            return;
        }

        $permissionId = DB::table('permissions')->where('key', 'ecole.view')->value('id');
        if (! $permissionId) {
            return;
        }

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

    public function down(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $ids = DB::table('roles')->whereIn('slug', ['etudiant', 'parent', 'enseignant'])->pluck('id');
        if ($ids->isEmpty()) {
            return;
        }

        if (Schema::hasTable('permission_role')) {
            DB::table('permission_role')->whereIn('role_id', $ids)->delete();
        }

        DB::table('roles')->whereIn('id', $ids)->delete();
    }
};
