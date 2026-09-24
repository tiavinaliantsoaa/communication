<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ProjetTableau extends Model
{
    protected $table = 'projet_tableaux';

    protected $fillable = ['nom', 'background_path'];

    public function listes(): HasMany
    {
        return $this->hasMany(ProjetListe::class)->orderBy('position');
    }

    public function getBackgroundUrlAttribute(): ?string
    {
        if (! $this->background_path) {
            return null;
        }

        return Storage::disk('public')->url($this->background_path);
    }

    public static function current(): self
    {
        $tableau = static::query()->first() ?? static::create(['nom' => 'Communication']);
        ProjetListe::ensureTermine($tableau);

        return $tableau;
    }
}
