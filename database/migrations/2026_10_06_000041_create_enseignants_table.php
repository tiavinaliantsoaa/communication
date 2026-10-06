<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enseignants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departement_id')->nullable()->constrained('departements')->nullOnDelete();
            $table->string('prenom');
            $table->string('nom');
            $table->string('fonction')->nullable();
            $table->string('email')->nullable();
            $table->string('telephone')->nullable();
            $table->string('matieres')->nullable();
            $table->text('biographie')->nullable();
            $table->text('formation')->nullable();
            $table->text('experience')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enseignants');
    }
};
