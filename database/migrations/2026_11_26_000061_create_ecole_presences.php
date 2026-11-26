<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ecole_presences')) {
            Schema::create('ecole_presences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('departement_id')->constrained('departements')->cascadeOnDelete();
                $table->foreignId('ecole_seance_id')->constrained('ecole_seances')->cascadeOnDelete();
                $table->foreignId('ecole_etudiant_id')->constrained('ecole_etudiants')->cascadeOnDelete();
                $table->string('statut', 20);
                $table->unsignedSmallInteger('minutes_retard')->nullable();
                $table->string('motif', 255)->nullable();
                $table->timestamps();

                $table->unique(['ecole_seance_id', 'ecole_etudiant_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ecole_presences');
    }
};
