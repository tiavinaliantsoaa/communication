<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmTask extends Model
{
    public const STATUT_OUVERTE = 'ouverte';
    public const STATUT_TERMINEE = 'terminee';

    public const TYPES = [
        'appel' => 'Appel',
        'relance' => 'Relance',
        'rdv' => 'Rendez-vous',
        'email' => 'E-mail',
        'suivi_dossier' => 'Suivi dossier',
        'autre' => 'Autre',
    ];

    protected $fillable = [
        'crm_candidate_id',
        'assigned_to',
        'created_by',
        'type',
        'commentaire',
        'due_at',
        'statut',
        'completed_at',
        'due_notified_at',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
        'due_notified_at' => 'datetime',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(CrmCandidate::class, 'crm_candidate_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_OUVERTE);
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query->open()->where('due_at', '<=', now());
    }

    public function scopeAssignedTo(Builder $query, int $userId): Builder
    {
        return $query->where('assigned_to', $userId);
    }

    public function isOpen(): bool
    {
        return $this->statut === self::STATUT_OUVERTE;
    }

    public function isDue(): bool
    {
        return $this->isOpen() && $this->due_at && $this->due_at->lte(now());
    }

    public function markCompleted(): void
    {
        $this->update([
            'statut' => self::STATUT_TERMINEE,
            'completed_at' => now(),
        ]);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getStatutLabelAttribute(): string
    {
        return $this->statut === self::STATUT_TERMINEE ? 'Terminée' : 'Ouverte';
    }

    public function getStatutColorAttribute(): string
    {
        if ($this->statut === self::STATUT_TERMINEE) {
            return 'bg-green-50 text-green-700 ring-green-600/20';
        }

        if ($this->isDue()) {
            return 'bg-red-50 text-red-700 ring-red-600/20';
        }

        return 'bg-amber-50 text-amber-700 ring-amber-600/20';
    }

    public function toBubbleArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type_label,
            'commentaire' => $this->commentaire,
            'due_at' => $this->due_at?->format('d/m/Y H:i'),
            'due_human' => $this->due_at?->diffForHumans(),
            'candidate' => $this->candidate?->full_name ?? 'Candidat',
            'url' => route('crm.candidats.show', [
                'candidat' => $this->crm_candidate_id,
                'tab' => 'tasks',
            ]),
            'overdue' => $this->isDue(),
        ];
    }
}
