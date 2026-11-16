<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EcoleMatiere extends Model
{
    protected $fillable = [
        'departement_id',
        'nom',
    ];

    public function seances(): HasMany
    {
        return $this->hasMany(EcoleSeance::class);
    }
}
