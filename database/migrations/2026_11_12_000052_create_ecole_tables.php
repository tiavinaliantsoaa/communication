<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecole_groupes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departement_id')->constrained('departements')->cascadeOnDelete();
            $table->string('nom', 50);
            $table->timestamps();

            $table->unique(['departement_id', 'nom']);
        });

        Schema::create('ecole_etudiants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departement_id')->constrained('departements')->cascadeOnDelete();
            $table->foreignId('ecole_groupe_id')->constrained('ecole_groupes')->restrictOnDelete();
            $table->string('prenom');
            $table->string('nom');
            $table->timestamps();
        });

        Schema::create('ecole_seances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departement_id')->constrained('departements')->cascadeOnDelete();
            $table->foreignId('ecole_groupe_id')->constrained('ecole_groupes')->cascadeOnDelete();
            $table->unsignedTinyInteger('jour');
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->string('matiere');
            $table->string('salle')->nullable();
            $table->timestamps();

            $table->index(['ecole_groupe_id', 'jour']);
        });

        Schema::create('ecole_assiduites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departement_id')->constrained('departements')->cascadeOnDelete();
            $table->foreignId('ecole_etudiant_id')->constrained('ecole_etudiants')->cascadeOnDelete();
            $table->date('date');
            $table->string('statut', 20);
            $table->timestamps();

            $table->unique(['ecole_etudiant_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecole_assiduites');
        Schema::dropIfExists('ecole_seances');
        Schema::dropIfExists('ecole_etudiants');
        Schema::dropIfExists('ecole_groupes');
    }
};
