<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcoleSeance extends Model
{
    protected $fillable = [
        'departement_id',
        'ecole_groupe_id',
        'date',
        'jour',
        'heure_debut',
        'heure_fin',
        'matiere',
        'salle',
    ];

    protected $casts = [
        'jour' => 'integer',
        'date' => 'date',
    ];

    public function groupe(): BelongsTo
    {
        return $this->belongsTo(EcoleGroupe::class, 'ecole_groupe_id');
    }

    public function heure(string $attribute): string
    {
        return substr((string) $this->getAttribute($attribute), 0, 5);
    }
}
