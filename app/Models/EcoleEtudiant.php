<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EcoleEtudiant extends Model
{
    protected $fillable = [
        'departement_id',
        'ecole_groupe_id',
        'crm_candidate_id',
        'prenom',
        'nom',
        'nom_officiel',
        'nom_caracteres',
        'palier',
        'nom_utilisateur',
        'date_naissance',
        'site_web',
        'email',
        'code_verrouillage',
        'matricule',
        'domicile',
    ];

    protected $casts = [
        'date_naissance' => 'date',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('annee', function ($query) {
            $query->whereHas('groupe');
        });
    }

    public function groupe(): BelongsTo
    {
        return $this->belongsTo(EcoleGroupe::class, 'ecole_groupe_id');
    }

    public function candidat(): BelongsTo
    {
        return $this->belongsTo(CrmCandidate::class, 'crm_candidate_id');
    }

    public function inscriptionLabel(): string
    {
        return 'Inscris dans Groupe '.($this->groupe?->nom ?? '');
    }

    public function assiduites(): HasMany
    {
        return $this->hasMany(EcoleAssiduite::class);
    }

    public function getNomCompletAttribute(): string
    {
        return trim($this->prenom.' '.$this->nom);
    }

    public function nomCompletOfficiel(): string
    {
        $saisi = trim((string) $this->nom_officiel);

        return $saisi !== '' ? $saisi : trim($this->nom.' '.$this->prenom);
    }

    public function palierAffiche(): string
    {
        $saisi = trim((string) $this->palier);
        if ($saisi !== '') {
            return $saisi;
        }

        return trim((string) ($this->candidat?->niveau_etudes ?: $this->candidat?->programme));
    }

    public function courriel(): string
    {
        return trim((string) ($this->email ?: $this->candidat?->email));
    }

    public function domicileAffiche(): string
    {
        return trim((string) ($this->domicile ?: $this->candidat?->adresse));
    }

    public function naissance(): ?Carbon
    {
        return $this->date_naissance ?: $this->candidat?->date_naissance;
    }

    public function ageLibelle(): ?string
    {
        $naissance = $this->naissance();
        if (! $naissance) {
            return null;
        }

        $diff = $naissance->diff(now());
        $annees = $diff->y.' '.($diff->y > 1 ? 'années' : 'année');

        return $annees.', '.$diff->m.' mois';
    }

    public function historiqueEcole(): string
    {
        if ($this->crm_candidate_id) {
            $lignes = static::withoutGlobalScopes(['annee'])
                ->where('crm_candidate_id', $this->crm_candidate_id)
                ->with(['groupe' => fn ($query) => $query->withoutGlobalScopes(['annee'])])
                ->get()
                ->map(function (self $eleve) {
                    $groupe = $eleve->groupe;
                    if (! $groupe) {
                        return null;
                    }

                    return $groupe->annee_universitaire.': '.$groupe->nom;
                })
                ->filter()
                ->unique()
                ->values();

            if ($lignes->isNotEmpty()) {
                return $lignes->implode(' · ');
            }
        }

        if ($this->groupe) {
            return $this->groupe->annee_universitaire.': '.$this->groupe->nom;
        }

        return '';
    }
}
