<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('permissions')) {
            \App\Models\Permission::syncCatalog();

            // Rename permission group label for ESCM calendar
            DB::table('permissions')
                ->where('key', 'like', 'calendrier_editorial.%')
                ->where('group', 'Calendrier éditorial')
                ->update(['group' => 'Calendrier ESCM']);

            $editorialKeys = [
                'calendrier_editorial.view',
                'calendrier_editorial.create',
                'calendrier_editorial.update',
                'calendrier_editorial.delete',
                'calendrier_editorial.validate',
            ];
            $krismasyKeys = [
                'calendrier_krismasy.view',
                'calendrier_krismasy.create',
                'calendrier_krismasy.update',
                'calendrier_krismasy.delete',
                'calendrier_krismasy.validate',
            ];

            $editorialIds = DB::table('permissions')->whereIn('key', $editorialKeys)->pluck('id', 'key');
            $krismasyIds = DB::table('permissions')->whereIn('key', $krismasyKeys)->pluck('id', 'key');

            foreach ($editorialKeys as $index => $editorialKey) {
                $fromId = $editorialIds[$editorialKey] ?? null;
                $toId = $krismasyIds[$krismasyKeys[$index]] ?? null;
                if (! $fromId || ! $toId) {
                    continue;
                }

                $roleIds = DB::table('permission_role')->where('permission_id', $fromId)->pluck('role_id');
                foreach ($roleIds as $roleId) {
                    DB::table('permission_role')->insertOrIgnore([
                        'role_id' => $roleId,
                        'permission_id' => $toId,
                    ]);
                }

                $userIds = DB::table('permission_user')->where('permission_id', $fromId)->pluck('user_id');
                foreach ($userIds as $userId) {
                    DB::table('permission_user')->insertOrIgnore([
                        'user_id' => $userId,
                        'permission_id' => $toId,
                    ]);
                }
            }
        }

        if (Schema::hasTable('departement_menus')) {
            $now = now();
            $rows = DB::table('departement_menus')
                ->where('menu_key', 'calendrier_editorial')
                ->get(['departement_id', 'enabled']);

            foreach ($rows as $row) {
                DB::table('departement_menus')->updateOrInsert(
                    [
                        'departement_id' => $row->departement_id,
                        'menu_key' => 'calendrier_krismasy',
                    ],
                    [
                        'enabled' => (bool) $row->enabled,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('departement_menus')) {
            DB::table('departement_menus')->where('menu_key', 'calendrier_krismasy')->delete();
        }

        if (Schema::hasTable('permissions')) {
            DB::table('permissions')
                ->where('key', 'like', 'calendrier_editorial.%')
                ->where('group', 'Calendrier ESCM')
                ->update(['group' => 'Calendrier éditorial']);

            $ids = DB::table('permissions')
                ->where('key', 'like', 'calendrier_krismasy.%')
                ->pluck('id');

            if ($ids->isNotEmpty()) {
                DB::table('permission_role')->whereIn('permission_id', $ids)->delete();
                DB::table('permission_user')->whereIn('permission_id', $ids)->delete();
                DB::table('permissions')->whereIn('id', $ids)->delete();
            }
        }
    }
};
