<?php

use App\Models\CrmCandidate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_candidates', function (Blueprint $table) {
            $table->boolean('paiement_frais_test')->default(false)->after('notes');
            $table->boolean('validation_test')->default(false)->after('paiement_frais_test');
            $table->boolean('lettre_admission')->default(false)->after('validation_test');
            $table->boolean('paiement_acompte')->default(false)->after('lettre_admission');
            $table->boolean('paiement_totalite')->default(false)->after('paiement_acompte');
        });

        // Migrate legacy statuses toward the new funnel keys
        $map = [
            'nouveau' => 'prospect',
            'contacte' => 'prospect',
            'interesse' => 'decouverte',
            'dossier_recu' => 'intention_deposee',
            'entretien' => 'evaluation',
            'admis' => 'inscription',
            'inscrit' => 'inscrit',
            'perdu' => 'prospect',
        ];

        foreach ($map as $from => $to) {
            DB::table('crm_candidates')->where('statut', $from)->update(['statut' => $to]);
        }

        // Recompute from fields where possible
        CrmCandidate::query()->each(function (CrmCandidate $candidate) {
            $candidate->forceFill([
                'statut' => CrmCandidate::resolveStatutFromAttributes($candidate->getAttributes()),
            ])->saveQuietly();
        });
    }

    public function down(): void
    {
        Schema::table('crm_candidates', function (Blueprint $table) {
            $table->dropColumn([
                'paiement_frais_test',
                'validation_test',
                'lettre_admission',
                'paiement_acompte',
                'paiement_totalite',
            ]);
        });
    }
};
