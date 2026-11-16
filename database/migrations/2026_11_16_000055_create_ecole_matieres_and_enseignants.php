<?php

use App\Models\Departement;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ecole_matieres')) {
            Schema::create('ecole_matieres', function (Blueprint $table) {
                $table->id();
                $table->foreignId('departement_id')->constrained('departements')->cascadeOnDelete();
                $table->string('nom', 120);
                $table->timestamps();

                $table->unique(['departement_id', 'nom']);
            });
        }

        if (! Schema::hasTable('ecole_enseignants')) {
            Schema::create('ecole_enseignants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('departement_id')->constrained('departements')->cascadeOnDelete();
                $table->string('prenom');
                $table->string('nom');
                $table->timestamps();
            });
        }

        Schema::table('ecole_seances', function (Blueprint $table) {
            if (! Schema::hasColumn('ecole_seances', 'ecole_matiere_id')) {
                $table->foreignId('ecole_matiere_id')->nullable()->after('ecole_groupe_id')->constrained('ecole_matieres')->nullOnDelete();
            }
            if (! Schema::hasColumn('ecole_seances', 'ecole_enseignant_id')) {
                $table->foreignId('ecole_enseignant_id')->nullable()->after('ecole_matiere_id')->constrained('ecole_enseignants')->nullOnDelete();
            }
            if (! Schema::hasColumn('ecole_seances', 'professeur')) {
                $table->string('professeur')->nullable()->after('matiere');
            }
        });

        if (! Schema::hasTable('departement_menus')) {
            return;
        }

        $now = now();
        foreach (Departement::query()->get() as $departement) {
            foreach (['ecole_matieres', 'ecole_enseignants'] as $key) {
                DB::table('departement_menus')->updateOrInsert(
                    ['departement_id' => $departement->id, 'menu_key' => $key],
                    [
                        'enabled' => $departement->slug === 'pedagogique',
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
            DB::table('departement_menus')->whereIn('menu_key', ['ecole_matieres', 'ecole_enseignants'])->delete();
        }

        if (Schema::hasTable('ecole_seances')) {
            Schema::table('ecole_seances', function (Blueprint $table) {
                if (Schema::hasColumn('ecole_seances', 'ecole_matiere_id')) {
                    $table->dropConstrainedForeignId('ecole_matiere_id');
                }
                if (Schema::hasColumn('ecole_seances', 'ecole_enseignant_id')) {
                    $table->dropConstrainedForeignId('ecole_enseignant_id');
                }
                if (Schema::hasColumn('ecole_seances', 'professeur')) {
                    $table->dropColumn('professeur');
                }
            });
        }

        Schema::dropIfExists('ecole_enseignants');
        Schema::dropIfExists('ecole_matieres');
    }
};
