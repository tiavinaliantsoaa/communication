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

    public function previewKind(): string
    {
        $name = strtolower((string) ($this->nom ?: $this->path ?: $this->url));
        if (preg_match('/\.(jpe?g|png|gif|webp)(\?.*)?$/', $name)) {
            return 'image';
        }
        if (preg_match('/\.pdf(\?.*)?$/', $name)) {
            return 'pdf';
        }
        if ($this->url && ! $this->path) {
            return 'link';
        }

        return 'file';
    }

    public function toBoardArray(): array
    {
        $kind = $this->previewKind();
        $preview = $this->path
            ? route('gestion-projet.pieces.preview', $this)
            : $this->url;

        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'url' => $this->public_url,
            'preview_url' => $preview,
            'download_url' => $this->path ? route('gestion-projet.pieces.download', $this) : $this->url,
            'kind' => $kind,
        ];
    }
}
