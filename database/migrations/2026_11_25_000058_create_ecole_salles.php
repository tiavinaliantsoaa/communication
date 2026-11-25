<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ecole_salles')) {
            Schema::create('ecole_salles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('departement_id')->constrained('departements')->cascadeOnDelete();
                $table->string('nom', 120);
                $table->unsignedInteger('capacite');
                $table->timestamps();

                $table->unique(['departement_id', 'nom']);
            });
        }

        if (Schema::hasTable('ecole_seances') && ! Schema::hasColumn('ecole_seances', 'ecole_salle_id')) {
            Schema::table('ecole_seances', function (Blueprint $table) {
                $table->foreignId('ecole_salle_id')->nullable()->constrained('ecole_salles')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ecole_seances') && Schema::hasColumn('ecole_seances', 'ecole_salle_id')) {
            Schema::table('ecole_seances', function (Blueprint $table) {
                $table->dropConstrainedForeignId('ecole_salle_id');
            });
        }

        Schema::dropIfExists('ecole_salles');
    }
};
