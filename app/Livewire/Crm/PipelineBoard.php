<?php

namespace App\Livewire\Crm;

use App\Models\CrmCandidate;
use Livewire\Component;

class PipelineBoard extends Component
{
    public string $search = '';

    public function getColumnsProperty(): array
    {
        $candidates = CrmCandidate::with('advisor')
            ->where('abandon', false)
            ->searchNameOrPhone($this->search)
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
        $columns = $this->columns;

        return view('livewire.crm.pipeline-board', [
            'columns' => $columns,
            'matchCount' => collect($columns)->sum(fn (array $column) => $column['candidates']->count()),
        ]);
    }
}
