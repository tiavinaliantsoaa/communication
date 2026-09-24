<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartementMenu extends Model
{
    protected $fillable = [
        'departement_id',
        'menu_key',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class);
    }
}
