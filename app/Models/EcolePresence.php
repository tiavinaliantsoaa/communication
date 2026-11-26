<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcolePresence extends Model
{
    public const STATUTS = [
        'present' => 'Présent',
        'absent' => 'Absent',
        'retard' => 'En retard',
    ];

    protected $fillable = [
        'departement_id',
        'ecole_seance_id',
        'ecole_etudiant_id',
        'statut',
        'minutes_retard',
        'motif',
    ];

    protected $casts = [
        'minutes_retard' => 'integer',
    ];

    public function seance(): BelongsTo
    {
        return $this->belongsTo(EcoleSeance::class, 'ecole_seance_id');
    }

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(EcoleEtudiant::class, 'ecole_etudiant_id');
    }
}
