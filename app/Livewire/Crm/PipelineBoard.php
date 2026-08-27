<?php

namespace App\Livewire\Crm;

use App\Models\CrmCandidate;
use Livewire\Component;

class PipelineBoard extends Component
{
    public function getColumnsProperty(): array
    {
        $candidates = CrmCandidate::with('advisor')
            ->where('abandon', false)
            ->orderBy('pipeline_order')
            ->orderByDesc('updated_at')
            ->get()
            ->groupBy('statut');

        $columns = [];
        foreach (CrmCandidate::STATUTS as $key => $label) {
            $columns[$key] = [
                'key' => $key,
                'label' => $label,
                'condition' => CrmCandidate::STATUT_CONDITIONS[$key] ?? ($key === 'prospect' ? 'Aucun critère atteint' : null),
                'candidates' => $candidates->get($key, collect())->values(),
            ];
        }

        return $columns;
    }

    public function render()
    {
        return view('livewire.crm.pipeline-board', [
            'columns' => $this->columns,
        ]);
    }
}
