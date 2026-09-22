<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departements', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique();
            $table->string('slug')->unique();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        $now = now();
        DB::table('departements')->insert([
            ['nom' => 'Communication', 'slug' => 'communication', 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['nom' => 'Commercial', 'slug' => 'commercial', 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['nom' => 'Pédagogique', 'slug' => 'pedagogique', 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $communicationId = (int) DB::table('departements')->where('slug', 'communication')->value('id');

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('departement_id')->nullable()->constrained('departements')->nullOnDelete();
        });

        DB::table('users')->whereNull('departement_id')->update([
            'departement_id' => $communicationId,
        ]);

        foreach ($this->scopedTables() as $table) {
            $this->attachDepartement($table, $communicationId);
        }

        Schema::table('budget_annuels', function (Blueprint $table) {
            $table->dropUnique(['annee']);
            $table->unique(['departement_id', 'annee']);
        });

        Schema::table('budgets', function (Blueprint $table) {
            $table->unique(['departement_id', 'annee', 'mois']);
        });
    }

    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropUnique(['departement_id', 'annee', 'mois']);
        });

        Schema::table('budget_annuels', function (Blueprint $table) {
            $table->dropUnique(['departement_id', 'annee']);
            $table->unique('annee');
        });

        foreach (array_reverse($this->scopedTables()) as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'departement_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropConstrainedForeignId('departement_id');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('departement_id');
        });

        Schema::dropIfExists('departements');
    }

    /**
     * @return list<string>
     */
    private function scopedTables(): array
    {
        return [
            'budgets',
            'budget_annuels',
            'depenses',
            'fournisseurs',
            'campagnes',
            'stocks',
            'stock_mouvements',
            'evenements',
            'editorial_events',
            'editorial_event_visuels',
            'tracked_links',
            'projet_tableaux',
            'projet_listes',
            'projet_etiquettes',
            'projet_cartes',
            'projet_checklists',
            'projet_checklist_items',
            'projet_commentaires',
            'projet_commentaire_images',
            'projet_commentaire_reactions',
            'projet_pieces_jointes',
            'projet_activites',
            'crm_candidates',
            'crm_notes',
            'crm_activities',
            'crm_intakes',
            'crm_programmes',
            'crm_document_types',
            'crm_candidate_documents',
            'crm_interactions',
            'activity_logs',
        ];
    }

    private function attachDepartement(string $table, int $departementId): void
    {
        if (! Schema::hasTable($table) || Schema::hasColumn($table, 'departement_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->foreignId('departement_id')->nullable()->constrained('departements')->nullOnDelete();
        });

        DB::table($table)->whereNull('departement_id')->update([
            'departement_id' => $departementId,
        ]);
    }
};
