<?php

namespace App\Livewire\Crm;

use App\Models\CrmCandidate;
use App\Services\CrmActivityLogger;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PipelineBoard extends Component
{
    public bool $saving = false;

    public function getColumnsProperty(): array
    {
        $candidates = CrmCandidate::with('advisor')
            ->orderBy('pipeline_order')
            ->orderByDesc('updated_at')
            ->get()
            ->groupBy('statut');

        $columns = [];
        foreach (CrmCandidate::STATUTS as $key => $label) {
            $columns[$key] = [
                'key' => $key,
                'label' => $label,
                'candidates' => $candidates->get($key, collect())->values(),
            ];
        }

        return $columns;
    }

    public function moveCandidate(int $candidateId, string $newStatus, array $orderedIds = []): void
    {
        if (! auth()->user()?->canAccess('crm.update')) {
            abort(403);
        }

        if (! array_key_exists($newStatus, CrmCandidate::STATUTS)) {
            return;
        }

        $this->saving = true;

        $candidate = CrmCandidate::findOrFail($candidateId);
        $oldStatus = $candidate->statut;

        DB::transaction(function () use ($candidate, $newStatus, $orderedIds, $oldStatus) {
            if ($oldStatus !== $newStatus) {
                $candidate->update([
                    'statut' => $newStatus,
                    'last_interaction_at' => now(),
                ]);
                app(CrmActivityLogger::class)->statusChanged($candidate, $oldStatus, $newStatus);
            }

            if ($orderedIds !== []) {
                foreach ($orderedIds as $index => $id) {
                    CrmCandidate::whereKey($id)->where('statut', $newStatus)->update([
                        'pipeline_order' => $index,
                    ]);
                }
            }
        });

        $this->saving = false;
        $this->dispatch('pipeline-updated');
    }

    public function render()
    {
        return view('livewire.crm.pipeline-board', [
            'columns' => $this->columns,
        ]);
    }
}
