<?php

use App\Models\CrmCandidate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        CrmCandidate::query()->each(function (CrmCandidate $candidate) {
            $candidate->forceFill([
                'statut' => CrmCandidate::resolveStatutFromAttributes($candidate->getAttributes()),
            ])->saveQuietly();
        });
    }

    public function down(): void
    {
        CrmCandidate::query()->each(function (CrmCandidate $candidate) {
            $attrs = $candidate->getAttributes();
            $programme = trim((string) ($attrs['programme'] ?? ''));
            $frais = (bool) ($attrs['paiement_frais_test'] ?? false);
            $validation = (bool) ($attrs['validation_test'] ?? false);
            $lettre = (bool) ($attrs['lettre_admission'] ?? false);
            $acompte = (bool) ($attrs['paiement_acompte'] ?? false);
            $totalite = (bool) ($attrs['paiement_totalite'] ?? false);

            $statut = 'prospect';
            if ($acompte || $totalite) {
                $statut = 'inscrit';
            } elseif ($lettre) {
                $statut = 'inscription';
            } elseif ($validation) {
                $statut = 'evaluation';
            } elseif ($frais) {
                $statut = 'intention_deposee';
            } elseif ($programme !== '') {
                $statut = 'decouverte';
            }

            $candidate->forceFill(['statut' => $statut])->saveQuietly();
        });
    }
};
