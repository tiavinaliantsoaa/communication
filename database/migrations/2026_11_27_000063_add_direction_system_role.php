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

        $exists = DB::table('roles')->where('slug', 'direction')->exists();
        if (! $exists) {
            DB::table('roles')->insert([
                'name' => 'Direction',
                'slug' => 'direction',
                'description' => 'Consultation des sections de l’application',
                'home_route' => 'dashboard',
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! Schema::hasTable('permissions') || ! Schema::hasTable('permission_role')) {
            return;
        }

        $directionId = DB::table('roles')->where('slug', 'direction')->value('id');
        if (! $directionId || DB::table('permission_role')->where('role_id', $directionId)->exists()) {
            return;
        }

        $viewIds = DB::table('permissions')->where('key', 'like', '%.view')->pluck('id');
        foreach ($viewIds as $viewId) {
            DB::table('permission_role')->insert([
                'role_id' => $directionId,
                'permission_id' => $viewId,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $ids = DB::table('roles')->where('slug', 'direction')->pluck('id');
        if ($ids->isEmpty()) {
            return;
        }

        if (Schema::hasTable('permission_role')) {
            DB::table('permission_role')->whereIn('role_id', $ids)->delete();
        }

        DB::table('roles')->where('slug', 'direction')->delete();
    }
};
