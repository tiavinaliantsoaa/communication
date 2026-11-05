<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnlineTest extends Model
{
    protected $fillable = [
        'departement_id',
        'nom',
        'description',
        'duree_minutes',
        'actif',
    ];

    protected $casts = [
        'duree_minutes' => 'integer',
        'actif' => 'boolean',
    ];

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(OnlineTestQuestion::class)->orderBy('position')->orderBy('id');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(OnlineTestInvitation::class);
    }

    public function pointsTotal(): float
    {
        return (float) $this->questions()->sum('points');
    }
}
