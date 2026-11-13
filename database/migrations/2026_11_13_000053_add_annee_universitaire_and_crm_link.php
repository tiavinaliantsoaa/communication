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
        Schema::table('ecole_groupes', function (Blueprint $table) {
            $table->string('annee_universitaire', 9)->nullable()->after('departement_id');
        });

        DB::table('ecole_groupes')->whereNull('annee_universitaire')->update([
            'annee_universitaire' => AnneeUniversitaire::current(),
        ]);

        Schema::table('ecole_groupes', function (Blueprint $table) {
            $table->dropUnique(['departement_id', 'nom']);
            $table->unique(['departement_id', 'annee_universitaire', 'nom'], 'ecole_groupes_dept_annee_nom_unique');
        });

        Schema::table('ecole_etudiants', function (Blueprint $table) {
            $table->foreignId('crm_candidate_id')->nullable()->after('ecole_groupe_id')->constrained('crm_candidates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ecole_etudiants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('crm_candidate_id');
        });

        Schema::table('ecole_groupes', function (Blueprint $table) {
            $table->dropUnique('ecole_groupes_dept_annee_nom_unique');
            $table->dropColumn('annee_universitaire');
            $table->unique(['departement_id', 'nom']);
        });
    }
};
