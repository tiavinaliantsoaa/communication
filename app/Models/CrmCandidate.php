<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmCandidate extends Model
{
    public const STATUTS = [
        'prospect' => 'Prospect',
        'intention_deposee' => 'Intention déposée',
        'evaluation' => 'Évaluation',
        'inscrit' => 'Inscrit',
    ];

    public const STATUT_CONDITIONS = [
        'intention_deposee' => 'Choix du programme',
        'evaluation' => 'Test effectué',
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
        'intention_deposee',
        'evaluation',
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

    /**
     * Digits only, then Madagascar national number without leading 0 / 261.
     * Ex. +261 34 19 700 12 / 0341970012 → 341970012
     */
    public static function phoneCore(?string $value): string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?: '';
        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (str_starts_with($digits, '261') && strlen($digits) > 3) {
            $digits = substr($digits, 3);
        }
        if (str_starts_with($digits, '0') && strlen($digits) > 1) {
            $digits = substr($digits, 1);
        }

        return $digits;
    }

    public function scopeSearchNameOrPhone(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        $like = '%'.$term.'%';
        $digits = preg_replace('/\D+/', '', $term) ?: '';
        $phoneCore = self::phoneCore($term);
        $driver = $query->getConnection()->getDriverName();
        $fullName = $driver === 'sqlite'
            ? "(prenom || ' ' || nom)"
            : "CONCAT(prenom, ' ', nom)";
        $fullNameRev = $driver === 'sqlite'
            ? "(nom || ' ' || prenom)"
            : "CONCAT(nom, ' ', prenom)";
        $phoneDigits = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(telephone, ''), ' ', ''), '-', ''), '.', ''), '+', ''), '/', '')";

        // National number without leading 0 / country code 261 (MySQL + SQLite)
        if ($driver === 'sqlite') {
            $phoneCoreExpr = "CASE
                WHEN REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(telephone, ''), ' ', ''), '-', ''), '.', ''), '+', ''), '/', '') LIKE '00261%'
                    THEN SUBSTR(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(telephone, ''), ' ', ''), '-', ''), '.', ''), '+', ''), '/', ''), 6)
                WHEN REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(telephone, ''), ' ', ''), '-', ''), '.', ''), '+', ''), '/', '') LIKE '261%'
                    THEN SUBSTR(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(telephone, ''), ' ', ''), '-', ''), '.', ''), '+', ''), '/', ''), 4)
                WHEN REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(telephone, ''), ' ', ''), '-', ''), '.', ''), '+', ''), '/', '') LIKE '0%'
                    THEN SUBSTR(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(telephone, ''), ' ', ''), '-', ''), '.', ''), '+', ''), '/', ''), 2)
                ELSE REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(telephone, ''), ' ', ''), '-', ''), '.', ''), '+', ''), '/', '')
            END";
        } else {
            $phoneCoreExpr = "CASE
                WHEN {$phoneDigits} LIKE '00261%' THEN SUBSTRING({$phoneDigits}, 6)
                WHEN {$phoneDigits} LIKE '261%' THEN SUBSTRING({$phoneDigits}, 4)
                WHEN {$phoneDigits} LIKE '0%' THEN SUBSTRING({$phoneDigits}, 2)
                ELSE {$phoneDigits}
            END";
        }

        return $query->where(function (Builder $inner) use ($like, $digits, $phoneCore, $fullName, $fullNameRev, $phoneDigits, $phoneCoreExpr) {
            $inner->where('prenom', 'like', $like)
                ->orWhere('nom', 'like', $like)
                ->orWhereRaw("{$fullName} LIKE ?", [$like])
                ->orWhereRaw("{$fullNameRev} LIKE ?", [$like])
                ->orWhere('telephone', 'like', $like)
                ->orWhere('email', 'like', $like);

            if ($digits !== '') {
                $inner->orWhereRaw("{$phoneDigits} LIKE ?", ['%'.$digits.'%']);
            }

            if ($phoneCore !== '' && strlen($phoneCore) >= 6) {
                $inner->orWhereRaw("{$phoneCoreExpr} LIKE ?", ['%'.$phoneCore.'%']);
            }
        });
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
            'intention_deposee' => 'bg-blue-50 text-blue-700',
            'evaluation' => 'bg-violet-50 text-violet-700',
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
        $testEffectue = (bool) ($attrs['validation_test'] ?? false);
        $acompte = (bool) ($attrs['paiement_acompte'] ?? false);
        $totalite = (bool) ($attrs['paiement_totalite'] ?? false);

        if ($acompte || $totalite) {
            return 'inscrit';
        }
        if ($testEffectue) {
            return 'evaluation';
        }
        if ($programme !== '') {
            return 'intention_deposee';
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

    /** Super Admin peut encore modifier une fiche inscrite ; les autres rôles non. */
    public function canEditProfile(?User $user = null): bool
    {
        if (! $this->isProfileLocked()) {
            return true;
        }

        return (bool) ($user ?? auth()->user())?->isSuperAdmin();
    }

    /** Une fois créée, la source n’est modifiable que par un Super Admin. */
    public function canEditSource(?User $user = null): bool
    {
        return (bool) ($user ?? auth()->user())?->isSuperAdmin();
    }

    public function touchInteraction(): void
    {
        $this->forceFill(['last_interaction_at' => now()])->saveQuietly();
    }
}
