<?php

use App\Models\Departement;
use App\Models\Permission;
use App\Support\NavbarMenu;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('permissions')) {
            Permission::syncCatalog();

            $permissionId = DB::table('permissions')->where('key', 'ecole.view')->value('id');
            if ($permissionId) {
                foreach (DB::table('roles')->whereIn('slug', ['super_admin', 'administrateur'])->pluck('id') as $roleId) {
                    DB::table('permission_role')->insertOrIgnore([
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ]);
                }

                $userIds = DB::table('users')
                    ->whereIn('role', ['super_admin', 'administrateur'])
                    ->orWhereIn('departement_id', function ($query) {
                        $query->select('id')->from('departements')->where('slug', 'pedagogique');
                    })
                    ->pluck('id');

                foreach ($userIds as $userId) {
                    DB::table('permission_user')->insertOrIgnore([
                        'user_id' => $userId,
                        'permission_id' => $permissionId,
                    ]);
                }
            }
        }

        if (! Schema::hasTable('departement_menus')) {
            return;
        }

        $now = now();
        foreach (Departement::query()->get() as $departement) {
            foreach (NavbarMenu::schoolKeys() as $key) {
                DB::table('departement_menus')->updateOrInsert(
                    ['departement_id' => $departement->id, 'menu_key' => $key],
                    [
                        'enabled' => NavbarMenu::enabledByDefault($departement, $key),
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
            DB::table('departement_menus')->whereIn('menu_key', NavbarMenu::schoolKeys())->delete();
        }

        if (! Schema::hasTable('permissions')) {
            return;
        }

        $ids = DB::table('permissions')->where('key', 'ecole.view')->pluck('id');
        if ($ids->isEmpty()) {
            return;
        }

        DB::table('permission_role')->whereIn('permission_id', $ids)->delete();
        DB::table('permission_user')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
