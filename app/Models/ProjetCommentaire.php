<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjetCommentaire extends Model
{
    protected $table = 'projet_commentaires';

    protected $fillable = ['projet_carte_id', 'user_id', 'contenu'];

    public function carte(): BelongsTo
    {
        return $this->belongsTo(ProjetCarte::class, 'projet_carte_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProjetCommentaireImage::class, 'projet_commentaire_id')->orderBy('ordre');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(ProjetCommentaireReaction::class, 'projet_commentaire_id');
    }
}
