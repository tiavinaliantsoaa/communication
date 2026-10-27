<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('it_tickets')) {
            Schema::create('it_tickets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('departement_id')->nullable()->constrained('departements')->nullOnDelete();
                $table->string('titre');
                $table->text('description');
                $table->text('resultat_attendu')->nullable();
                $table->string('impact', 32)->nullable();
                $table->string('statut', 32)->default('en_attente');
                $table->text('admin_notes')->nullable();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('statut_updated_at')->nullable();
                $table->timestamps();

                $table->index(['statut', 'created_at']);
                $table->index(['user_id', 'created_at']);
            });
        }

        if (Schema::hasTable('permissions')) {
            \App\Models\Permission::syncCatalog();

            $keys = [
                'it_ticketing.view',
                'it_ticketing.create',
                'it_ticketing.update',
                'it_ticketing.delete',
                'it_ticketing.manage',
            ];
            $ids = DB::table('permissions')->whereIn('key', $keys)->pluck('id', 'key');

            // Grant view/create to roles that already have stocks.view
            $stockViewId = DB::table('permissions')->where('key', 'stocks.view')->value('id');
            if ($stockViewId) {
                $roleIds = DB::table('permission_role')->where('permission_id', $stockViewId)->pluck('role_id');
                foreach ($roleIds as $roleId) {
                    foreach (['it_ticketing.view', 'it_ticketing.create'] as $key) {
                        if (! isset($ids[$key])) {
                            continue;
                        }
                        DB::table('permission_role')->insertOrIgnore([
                            'role_id' => $roleId,
                            'permission_id' => $ids[$key],
                        ]);
                    }
                }

                $userIds = DB::table('permission_user')->where('permission_id', $stockViewId)->pluck('user_id');
                foreach ($userIds as $userId) {
                    foreach (['it_ticketing.view', 'it_ticketing.create'] as $key) {
                        if (! isset($ids[$key])) {
                            continue;
                        }
                        DB::table('permission_user')->insertOrIgnore([
                            'user_id' => $userId,
                            'permission_id' => $ids[$key],
                        ]);
                    }
                }
            }
        }

        if (Schema::hasTable('departement_menus')) {
            $now = now();
            $departementIds = DB::table('departements')->pluck('id');
            foreach ($departementIds as $departementId) {
                DB::table('departement_menus')->updateOrInsert(
                    [
                        'departement_id' => $departementId,
                        'menu_key' => 'it_ticketing',
                    ],
                    [
                        'enabled' => true,
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
            DB::table('departement_menus')->where('menu_key', 'it_ticketing')->delete();
        }

        if (Schema::hasTable('permissions')) {
            $ids = DB::table('permissions')->where('key', 'like', 'it_ticketing.%')->pluck('id');
            if ($ids->isNotEmpty()) {
                DB::table('permission_role')->whereIn('permission_id', $ids)->delete();
                DB::table('permission_user')->whereIn('permission_id', $ids)->delete();
                DB::table('permissions')->whereIn('id', $ids)->delete();
            }
        }

        Schema::dropIfExists('it_tickets');
    }
};
