<?php

use App\Support\AnneeUniversitaire;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ecole_groupes', 'annee_universitaire')) {
            Schema::table('ecole_groupes', function (Blueprint $table) {
                $table->string('annee_universitaire', 9)->nullable()->after('departement_id');
            });
        }

        DB::table('ecole_groupes')->whereNull('annee_universitaire')->update([
            'annee_universitaire' => AnneeUniversitaire::current(),
        ]);

        // The new unique index starts with departement_id, so it can replace
        // the index MySQL uses for the departement foreign key.
        if (! Schema::hasIndex('ecole_groupes', 'ecole_groupes_dept_annee_nom_unique')) {
            Schema::table('ecole_groupes', function (Blueprint $table) {
                $table->unique(['departement_id', 'annee_universitaire', 'nom'], 'ecole_groupes_dept_annee_nom_unique');
            });
        }

        if (Schema::hasIndex('ecole_groupes', 'ecole_groupes_departement_id_nom_unique')) {
            Schema::table('ecole_groupes', function (Blueprint $table) {
                $table->dropUnique(['departement_id', 'nom']);
            });
        }

        if (! Schema::hasColumn('ecole_etudiants', 'crm_candidate_id')) {
            Schema::table('ecole_etudiants', function (Blueprint $table) {
                $table->foreignId('crm_candidate_id')->nullable()->after('ecole_groupe_id')->constrained('crm_candidates')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ecole_etudiants', 'crm_candidate_id')) {
            Schema::table('ecole_etudiants', function (Blueprint $table) {
                $table->dropConstrainedForeignId('crm_candidate_id');
            });
        }

        if (Schema::hasColumn('ecole_groupes', 'annee_universitaire')
            && ! Schema::hasIndex('ecole_groupes', 'ecole_groupes_departement_id_nom_unique')) {
            Schema::table('ecole_groupes', function (Blueprint $table) {
                $table->unique(['departement_id', 'nom']);
            });
        }

        if (Schema::hasIndex('ecole_groupes', 'ecole_groupes_dept_annee_nom_unique')) {
            Schema::table('ecole_groupes', function (Blueprint $table) {
                $table->dropUnique('ecole_groupes_dept_annee_nom_unique');
            });
        }

        if (Schema::hasColumn('ecole_groupes', 'annee_universitaire')) {
            Schema::table('ecole_groupes', function (Blueprint $table) {
                $table->dropColumn('annee_universitaire');
            });
        }
    }
};
