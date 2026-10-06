<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Enseignant extends Model
{
    protected $fillable = [
        'prenom',
        'nom',
        'fonction',
        'email',
        'telephone',
        'matieres',
        'biographie',
        'formation',
        'experience',
        'photo_path',
    ];

    public function getNomCompletAttribute(): string
    {
        return trim($this->prenom.' '.$this->nom);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }

        return Storage::disk('public')->url($this->photo_path);
    }

    public function getInitialesAttribute(): string
    {
        return mb_strtoupper(mb_substr($this->prenom, 0, 1).mb_substr($this->nom, 0, 1));
    }

    protected static function booted(): void
    {
        static::deleting(function (self $enseignant) {
            if ($enseignant->photo_path) {
                Storage::disk('public')->delete($enseignant->photo_path);
            }
        });
    }
}
