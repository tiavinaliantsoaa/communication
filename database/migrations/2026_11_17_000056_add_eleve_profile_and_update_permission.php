<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecole_etudiants', function (Blueprint $table) {
            if (! Schema::hasColumn('ecole_etudiants', 'nom_officiel')) {
                $table->string('nom_officiel', 150)->nullable();
            }
            if (! Schema::hasColumn('ecole_etudiants', 'nom_caracteres')) {
                $table->string('nom_caracteres', 150)->nullable();
            }
            if (! Schema::hasColumn('ecole_etudiants', 'palier')) {
                $table->string('palier', 80)->nullable();
            }
            if (! Schema::hasColumn('ecole_etudiants', 'nom_utilisateur')) {
                $table->string('nom_utilisateur', 80)->nullable();
            }
            if (! Schema::hasColumn('ecole_etudiants', 'date_naissance')) {
                $table->date('date_naissance')->nullable();
            }
            if (! Schema::hasColumn('ecole_etudiants', 'site_web')) {
                $table->string('site_web')->nullable();
            }
            if (! Schema::hasColumn('ecole_etudiants', 'email')) {
                $table->string('email')->nullable();
            }
            if (! Schema::hasColumn('ecole_etudiants', 'code_verrouillage')) {
                $table->string('code_verrouillage', 40)->nullable();
            }
            if (! Schema::hasColumn('ecole_etudiants', 'matricule')) {
                $table->string('matricule', 40)->nullable();
            }
            if (! Schema::hasColumn('ecole_etudiants', 'domicile')) {
                $table->string('domicile')->nullable();
            }
        });

        if (! Schema::hasTable('permissions')) {
            return;
        }

        Permission::syncCatalog();

        $permissionId = DB::table('permissions')->where('key', 'ecole.update')->value('id');
        if (! $permissionId) {
            return;
        }

        $userIds = DB::table('users')
            ->whereIn('departement_id', function ($query) {
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

    public function down(): void
    {
        Schema::table('ecole_etudiants', function (Blueprint $table) {
            $columns = [
                'nom_officiel',
                'nom_caracteres',
                'palier',
                'nom_utilisateur',
                'date_naissance',
                'site_web',
                'email',
                'code_verrouillage',
                'matricule',
                'domicile',
            ];
            $present = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn('ecole_etudiants', $column)));
            if ($present !== []) {
                $table->dropColumn($present);
            }
        });

        if (! Schema::hasTable('permissions')) {
            return;
        }

        $ids = DB::table('permissions')->where('key', 'ecole.update')->pluck('id');
        if ($ids->isEmpty()) {
            return;
        }

        DB::table('permission_role')->whereIn('permission_id', $ids)->delete();
        DB::table('permission_user')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
