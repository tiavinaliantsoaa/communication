<?php

namespace App\Services;

use App\Models\CrmActivity;
use App\Models\CrmCandidate;
use App\Models\User;

class CrmActivityLogger
{
    public function log(
        CrmCandidate $candidate,
        string $type,
        string $title,
        ?string $description = null,
        ?array $meta = null,
        ?User $user = null
    ): CrmActivity {
        return CrmActivity::create([
            'crm_candidate_id' => $candidate->id,
            'user_id' => ($user ?? auth()->user())?->id,
            'type' => $type,
            'title' => $title,
            'description' => $description,
            'meta' => $meta,
            'created_at' => now(),
        ]);
    }

    public function created(CrmCandidate $candidate, ?User $user = null): CrmActivity
    {
        return $this->log(
            $candidate,
            CrmActivity::TYPE_CREATED,
            'Candidat créé',
            'Fiche créée pour '.$candidate->full_name,
            null,
            $user
        );
    }

    public function updated(CrmCandidate $candidate, ?User $user = null): CrmActivity
    {
        return $this->log(
            $candidate,
            CrmActivity::TYPE_UPDATED,
            'Candidat modifié',
            'Les informations de '.$candidate->full_name.' ont été mises à jour',
            null,
            $user
        );
    }

    public function statusChanged(CrmCandidate $candidate, string $from, string $to, ?User $user = null): CrmActivity
    {
        $fromLabel = CrmCandidate::STATUTS[$from] ?? $from;
        $toLabel = CrmCandidate::STATUTS[$to] ?? $to;

        return $this->log(
            $candidate,
            CrmActivity::TYPE_STATUS,
            'Statut modifié',
            $fromLabel.' → '.$toLabel,
            ['from' => $from, 'to' => $to],
            $user
        );
    }

    public function noteAdded(CrmCandidate $candidate, ?User $user = null): CrmActivity
    {
        return $this->log(
            $candidate,
            CrmActivity::TYPE_NOTE,
            'Note ajoutée',
            null,
            null,
            $user
        );
    }
}
