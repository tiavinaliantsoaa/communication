<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProjetCommentaireImage extends Model
{
    protected $table = 'projet_commentaire_images';

    protected $fillable = [
        'projet_commentaire_id',
        'path',
        'nom',
        'ordre',
    ];

    public function commentaire(): BelongsTo
    {
        return $this->belongsTo(ProjetCommentaire::class, 'projet_commentaire_id');
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    public function toArrayPayload(): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'url' => $this->url,
        ];
    }
}
