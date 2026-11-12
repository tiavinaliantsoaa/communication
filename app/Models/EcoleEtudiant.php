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
        'prenom',
        'nom',
    ];

    public function groupe(): BelongsTo
    {
        return $this->belongsTo(EcoleGroupe::class, 'ecole_groupe_id');
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
