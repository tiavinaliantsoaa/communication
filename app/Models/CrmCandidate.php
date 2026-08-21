<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmCandidate extends Model
{
    public const STATUTS = [
        'nouveau' => 'Nouveau',
        'contacte' => 'Contacté',
        'interesse' => 'Intéressé',
        'dossier_recu' => 'Dossier reçu',
        'entretien' => 'Entretien',
        'admis' => 'Admis',
        'inscrit' => 'Inscrit',
        'perdu' => 'Perdu',
    ];

    public const SOURCES = [
        'facebook' => 'Facebook',
        'website' => 'Website',
        'whatsapp' => 'WhatsApp',
        'referral' => 'Referral',
        'walk_in' => 'Walk-in',
        'other' => 'Other',
    ];

    public const GENRES = [
        'homme' => 'Homme',
        'femme' => 'Femme',
        'autre' => 'Autre',
    ];

    /** Funnel order for conversion chart (excluding perdu). */
    public const FUNNEL_STATUTS = [
        'nouveau',
        'contacte',
        'interesse',
        'dossier_recu',
        'entretien',
        'admis',
        'inscrit',
    ];

    protected $fillable = [
        'prenom',
        'nom',
        'genre',
        'date_naissance',
        'telephone',
        'email',
        'adresse',
        'programme',
        'annee_academique',
        'niveau_etudes',
        'etablissement_origine',
        'statut',
        'source',
        'advisor_id',
        'notes',
        'last_interaction_at',
        'created_by',
        'pipeline_order',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'last_interaction_at' => 'datetime',
        'pipeline_order' => 'integer',
    ];

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'advisor_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function crmNotes(): HasMany
    {
        return $this->hasMany(CrmNote::class)->latest();
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CrmActivity::class)->latest('created_at');
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->prenom.' '.$this->nom);
    }

    public function getStatutLabelAttribute(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    public function getSourceLabelAttribute(): string
    {
        return self::SOURCES[$this->source] ?? ($this->source ?: '—');
    }

    public function getGenreLabelAttribute(): string
    {
        return self::GENRES[$this->genre] ?? ($this->genre ?: '—');
    }

    public function getStatutColorAttribute(): string
    {
        return match ($this->statut) {
            'nouveau' => 'bg-slate-100 text-slate-700',
            'contacte' => 'bg-sky-50 text-sky-700',
            'interesse' => 'bg-blue-50 text-blue-700',
            'dossier_recu' => 'bg-indigo-50 text-indigo-700',
            'entretien' => 'bg-violet-50 text-violet-700',
            'admis' => 'bg-emerald-50 text-emerald-700',
            'inscrit' => 'bg-green-50 text-green-800',
            'perdu' => 'bg-red-50 text-red-700',
            default => 'bg-slate-100 text-slate-700',
        };
    }

    public function touchInteraction(): void
    {
        $this->forceFill(['last_interaction_at' => now()])->saveQuietly();
    }
}
