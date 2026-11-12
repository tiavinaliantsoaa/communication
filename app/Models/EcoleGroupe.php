<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EcoleGroupe extends Model
{
    protected $fillable = [
        'departement_id',
        'nom',
    ];

    public function etudiants(): HasMany
    {
        return $this->hasMany(EcoleEtudiant::class);
    }

    public function seances(): HasMany
    {
        return $this->hasMany(EcoleSeance::class);
    }
}
