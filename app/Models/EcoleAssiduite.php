<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcoleAssiduite extends Model
{
    public const STATUTS = [
        'present' => 'Présent',
        'absent' => 'Absent',
        'retard' => 'Retard',
    ];

    protected $fillable = [
        'departement_id',
        'ecole_etudiant_id',
        'date',
        'statut',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(EcoleEtudiant::class, 'ecole_etudiant_id');
    }

    public function getStatutLabelAttribute(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }
}
