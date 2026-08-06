<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotification extends Model
{
    protected $fillable = [
        'user_id',
        'actor_id',
        'projet_carte_id',
        'type',
        'titre',
        'description',
        'url',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function carte(): BelongsTo
    {
        return $this->belongsTo(ProjetCarte::class, 'projet_carte_id');
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function toAlerteArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'titre' => $this->titre,
            'description' => $this->description,
            'temps' => $this->created_at?->diffForHumans() ?? 'Récent',
            'url' => $this->url ?? route('gestion-projet.index'),
            'dismissible' => true,
            'perso' => true,
            'unread' => $this->read_at === null,
        ];
    }
}
