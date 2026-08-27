<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmCandidate extends Model
{
    public const STATUTS = [
        'prospect' => 'Prospect',
        'decouverte' => 'Découverte',
        'intention_deposee' => 'Intention déposée',
        'evaluation' => 'Évaluation',
        'inscription' => 'Inscription',
        'inscrit' => 'Inscrit',
    ];

    public const STATUT_CONDITIONS = [
        'decouverte' => 'Choix du programme',
        'intention_deposee' => 'Paiement frais de test',
        'evaluation' => 'Validation du test',
        'inscription' => 'Lettre d’admission',
        'inscrit' => 'Paiement acompte ou totalité',
    ];

    public const SOURCES = [
        'facebook' => 'Facebook',
        'site_web' => 'Site Web',
        'salon' => 'Salon',
        'escm_tour' => 'ESCM Tour',
    ];

    public const GENRES = [
        'homme' => 'Homme',
        'femme' => 'Femme',
        'autre' => 'Autre',
    ];

    public const FUNNEL_STATUTS = [
        'prospect',
        'decouverte',
        'intention_deposee',
        'evaluation',
        'inscription',
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
        'contact_parent_1',
        'contact_parent_2',
        'programme',
        'annee_academique',
        'niveau_etudes',
        'etablissement_origine',
        'statut',
        'source',
        'facebook_profil_url',
        'escm_tour_ville',
        'advisor_id',
        'notes',
        'paiement_frais_test',
        'validation_test',
        'lettre_admission',
        'paiement_acompte',
        'paiement_totalite',
        'abandon',
        'abandon_raison',
        'glide_applicant_id',
        'glide_row_id',
        'last_interaction_at',
        'created_by',
        'pipeline_order',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'last_interaction_at' => 'datetime',
        'pipeline_order' => 'integer',
        'paiement_frais_test' => 'boolean',
        'validation_test' => 'boolean',
        'lettre_admission' => 'boolean',
        'paiement_acompte' => 'boolean',
        'paiement_totalite' => 'boolean',
        'abandon' => 'boolean',
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

    public function documents(): HasMany
    {
        return $this->hasMany(CrmCandidateDocument::class);
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(CrmInteraction::class)->latest();
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
            'prospect' => 'bg-slate-100 text-slate-700',
            'decouverte' => 'bg-sky-50 text-sky-700',
            'intention_deposee' => 'bg-blue-50 text-blue-700',
            'evaluation' => 'bg-violet-50 text-violet-700',
            'inscription' => 'bg-indigo-50 text-indigo-700',
            'inscrit' => 'bg-green-50 text-green-800',
            default => 'bg-slate-100 text-slate-700',
        };
    }

    /**
     * Highest pipeline stage reached from candidate info.
     *
     * @param  array<string, mixed>  $attrs
     */
    public static function resolveStatutFromAttributes(array $attrs): string
    {
        $programme = trim((string) ($attrs['programme'] ?? ''));
        $frais = (bool) ($attrs['paiement_frais_test'] ?? false);
        $validation = (bool) ($attrs['validation_test'] ?? false);
        $lettre = (bool) ($attrs['lettre_admission'] ?? false);
        $acompte = (bool) ($attrs['paiement_acompte'] ?? false);
        $totalite = (bool) ($attrs['paiement_totalite'] ?? false);

        if ($acompte || $totalite) {
            return 'inscrit';
        }
        if ($lettre) {
            return 'inscription';
        }
        if ($validation) {
            return 'evaluation';
        }
        if ($frais) {
            return 'intention_deposee';
        }
        if ($programme !== '') {
            return 'decouverte';
        }

        return 'prospect';
    }

    public function syncStatutFromInfo(): string
    {
        $statut = self::resolveStatutFromAttributes($this->getAttributes());
        if ($this->statut !== $statut) {
            $this->forceFill(['statut' => $statut])->saveQuietly();
        }

        return $statut;
    }

    /** Fiche verrouillée une fois le candidat inscrit (pas d’édition / suppression du profil). */
    public function isProfileLocked(): bool
    {
        return $this->statut === 'inscrit';
    }

    public function touchInteraction(): void
    {
        $this->forceFill(['last_interaction_at' => now()])->saveQuietly();
    }
}
