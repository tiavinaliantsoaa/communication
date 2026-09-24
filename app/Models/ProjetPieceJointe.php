<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjetPieceJointe extends Model
{
    protected $table = 'projet_pieces_jointes';

    protected $fillable = ['projet_carte_id', 'nom', 'path', 'url', 'uploaded_by'];

    public function carte(): BelongsTo
    {
        return $this->belongsTo(ProjetCarte::class, 'projet_carte_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getPublicUrlAttribute(): ?string
    {
        if ($this->url) {
            return $this->url;
        }

        if ($this->path) {
            return route('gestion-projet.pieces.download', $this);
        }

        return null;
    }
}
