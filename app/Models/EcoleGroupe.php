<?php

namespace App\Models;

use App\Support\AnneeUniversitaire;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EcoleGroupe extends Model
{
    protected $fillable = [
        'departement_id',
        'annee_universitaire',
        'nom',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('annee', function ($query) {
            $query->where($query->getModel()->getTable().'.annee_universitaire', AnneeUniversitaire::selected());
        });

        static::creating(function (self $groupe) {
            if (! $groupe->annee_universitaire) {
                $groupe->annee_universitaire = AnneeUniversitaire::selected();
            }
        });
    }

    public function etudiants(): HasMany
    {
        return $this->hasMany(EcoleEtudiant::class);
    }

    public function seances(): HasMany
    {
        return $this->hasMany(EcoleSeance::class);
    }
}
