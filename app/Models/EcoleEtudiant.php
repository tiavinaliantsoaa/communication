<?php

namespace App\Models;

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
}
