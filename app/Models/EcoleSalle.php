<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EcoleSalle extends Model
{
    protected $fillable = [
        'departement_id',
        'nom',
        'capacite',
    ];

    protected $casts = [
        'capacite' => 'integer',
    ];

    public function seances(): HasMany
    {
        return $this->hasMany(EcoleSeance::class);
    }
}
