<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmCandidate;

class PipelineController extends Controller
{
    public function __invoke()
    {
        $candidates = CrmCandidate::with('advisor')
            ->where('abandon', false)
            ->orderBy('pipeline_order')
            ->orderByDesc('updated_at')
            ->get();

        $columns = [];
        foreach (CrmCandidate::STATUTS as $key => $label) {
            $columns[] = [
                'key' => $key,
                'label' => $label,
                'condition' => CrmCandidate::STATUT_CONDITIONS[$key] ?? ($key === 'prospect' ? 'Aucun critère atteint' : null),
                'candidates' => $candidates
                    ->where('statut', $key)
                    ->map(fn (CrmCandidate $c) => [
                        'id' => $c->id,
                        'nom' => $c->full_name,
                        'telephone' => $c->telephone,
                        'programme' => $c->programme,
                        'advisor' => $c->advisor?->name,
                        'url' => route('crm.candidats.show', $c),
                    ])
                    ->values()
                    ->all(),
            ];
        }

        return view('crm.pipeline', ['columns' => $columns]);
    }
}
