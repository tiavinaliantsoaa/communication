<?php

use App\Models\Depense;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departement_id')->constrained('departements')->cascadeOnDelete();
            $table->string('nom');
            $table->string('slug');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['departement_id', 'slug']);
            $table->unique(['departement_id', 'nom']);
        });

        $now = now();
        $rows = [];
        foreach (DB::table('departements')->pluck('id') as $departementId) {
            $position = 0;
            foreach (Depense::CATEGORIES as $slug => $nom) {
                $rows[] = [
                    'departement_id' => $departementId,
                    'nom' => $nom,
                    'slug' => $slug,
                    'position' => $position++,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($rows !== []) {
            DB::table('depense_categories')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('depense_categories');
    }
};
