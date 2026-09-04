<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmCandidate;

class PipelineController extends Controller
{
    public function __invoke()
    {
        $candidates = CrmCandidate::with('advisor')
            ->orderBy('pipeline_order')
            ->orderByDesc('updated_at')
            ->get();

        $toCard = fn (CrmCandidate $c) => [
            'id' => $c->id,
            'nom' => $c->full_name,
            'telephone' => $c->telephone,
            'programme' => $c->programme,
            'advisor' => $c->advisor?->name,
            'url' => route('crm.candidats.show', $c),
        ];

        $actifs = $candidates->where('abandon', false);
        $columns = [];
        foreach (CrmCandidate::STATUTS as $key => $label) {
            $columns[] = [
                'key' => $key,
                'label' => $label,
                'tone' => null,
                'condition' => CrmCandidate::STATUT_CONDITIONS[$key] ?? ($key === 'prospect' ? 'Aucun critère atteint' : null),
                'candidates' => $actifs
                    ->where('statut', $key)
                    ->map($toCard)
                    ->values()
                    ->all(),
            ];
        }

        $columns[] = [
            'key' => 'abandon',
            'label' => 'Abandon',
            'tone' => 'danger',
            'condition' => 'Motif renseigné',
            'candidates' => $candidates
                ->where('abandon', true)
                ->map($toCard)
                ->values()
                ->all(),
        ];

        return view('crm.pipeline', ['columns' => $columns]);
    }
}
