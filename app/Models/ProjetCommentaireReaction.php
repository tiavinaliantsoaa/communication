<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjetCommentaireReaction extends Model
{
    public const EMOJIS = ['👍', '👎', '😄', '🎉', '😕', '❤️', '🚀', '👀'];

    protected $table = 'projet_commentaire_reactions';

    protected $fillable = [
        'projet_commentaire_id',
        'user_id',
        'emoji',
    ];

    public function commentaire(): BelongsTo
    {
        return $this->belongsTo(ProjetCommentaire::class, 'projet_commentaire_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
