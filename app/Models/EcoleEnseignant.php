<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EcoleEnseignant extends Model
{
    protected $fillable = [
        'departement_id',
        'prenom',
        'nom',
    ];

    public function getNomCompletAttribute(): string
    {
        return trim($this->prenom.' '.$this->nom);
    }

    public function seances(): HasMany
    {
        return $this->hasMany(EcoleSeance::class);
    }
}
