<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItTicket extends Model
{
    public const STATUTS = [
        'en_attente' => 'En attente',
        'en_cours' => 'En cours',
        'corrige' => 'Corrigé',
        'annule' => 'Annulé',
    ];

    public const IMPACTS = [
        'haut' => 'Haut',
        'moyen' => 'Moyen',
        'passable' => 'Passable',
    ];

    protected $fillable = [
        'user_id',
        'departement_id',
        'titre',
        'description',
        'resultat_attendu',
        'impact',
        'statut',
        'admin_notes',
        'updated_by',
        'statut_updated_at',
    ];

    protected $casts = [
        'statut_updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class);
    }

    public function getStatutLabelAttribute(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    public function getImpactLabelAttribute(): string
    {
        return self::IMPACTS[$this->impact] ?? ($this->impact ?: '—');
    }

    public function getImpactColorAttribute(): string
    {
        return match ($this->impact) {
            'haut' => 'bg-red-50 text-red-700 ring-red-600/20',
            'moyen' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
            'passable' => 'bg-slate-50 text-slate-600 ring-slate-500/20',
            default => 'bg-slate-50 text-slate-700 ring-slate-600/20',
        };
    }

    public function getStatutColorAttribute(): string
    {
        return match ($this->statut) {
            'en_attente' => 'bg-orange-50 text-orange-700 ring-orange-600/20',
            'en_cours' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
            'corrige' => 'bg-green-50 text-green-700 ring-green-600/20',
            'annule' => 'bg-slate-100 text-slate-600 ring-slate-500/20',
            default => 'bg-slate-50 text-slate-700 ring-slate-600/20',
        };
    }

    public function canBeManagedBy(?User $user = null): bool
    {
        $user ??= auth()->user();

        return (bool) $user?->isSuperAdmin();
    }

    public function canBeViewedBy(?User $user = null): bool
    {
        $user ??= auth()->user();
        if (! $user) {
            return false;
        }

        return $user->isSuperAdmin()
            || $user->isAdmin()
            || (int) $this->user_id === (int) $user->id;
    }
}
