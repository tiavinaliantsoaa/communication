<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('editorial_events') && ! Schema::hasColumn('editorial_events', 'service')) {
            Schema::table('editorial_events', function (Blueprint $table) {
                $table->string('service', 32)->default('editorial')->after('id');
                $table->index('service');
            });

            DB::table('editorial_events')
                ->where(function ($q) {
                    $q->whereNull('service')->orWhere('service', '');
                })
                ->update(['service' => 'editorial']);
        }

        if (Schema::hasTable('permissions')) {
            \App\Models\Permission::syncCatalog();

            $editorialKeys = [
                'calendrier_editorial.view',
                'calendrier_editorial.create',
                'calendrier_editorial.update',
                'calendrier_editorial.delete',
                'calendrier_editorial.validate',
            ];
            $saanKeys = [
                'calendrier_saan.view',
                'calendrier_saan.create',
                'calendrier_saan.update',
                'calendrier_saan.delete',
                'calendrier_saan.validate',
            ];

            $editorialIds = DB::table('permissions')->whereIn('key', $editorialKeys)->pluck('id', 'key');
            $saanIds = DB::table('permissions')->whereIn('key', $saanKeys)->pluck('id', 'key');

            foreach ($editorialKeys as $index => $editorialKey) {
                $fromId = $editorialIds[$editorialKey] ?? null;
                $toId = $saanIds[$saanKeys[$index]] ?? null;
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
                        'menu_key' => 'calendrier_saan',
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
            DB::table('departement_menus')->where('menu_key', 'calendrier_saan')->delete();
        }

        if (Schema::hasTable('permissions')) {
            $saanIds = DB::table('permissions')
                ->where('key', 'like', 'calendrier_saan.%')
                ->pluck('id');

            if ($saanIds->isNotEmpty()) {
                DB::table('permission_role')->whereIn('permission_id', $saanIds)->delete();
                DB::table('permission_user')->whereIn('permission_id', $saanIds)->delete();
                DB::table('permissions')->whereIn('id', $saanIds)->delete();
            }
        }

        if (Schema::hasTable('editorial_events') && Schema::hasColumn('editorial_events', 'service')) {
            Schema::table('editorial_events', function (Blueprint $table) {
                $table->dropIndex(['service']);
                $table->dropColumn('service');
            });
        }
    }
};
