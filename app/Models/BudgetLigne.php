<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetLigne extends Model
{
    protected $fillable = [
        'budget_id',
        'depense_categorie_id',
        'montant',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(DepenseCategorie::class, 'depense_categorie_id');
    }
}
