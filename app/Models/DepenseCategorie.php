<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DepenseCategorie extends Model
{
    protected $fillable = [
        'departement_id',
        'nom',
        'slug',
        'position',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class);
    }

    public static function options(): array
    {
        return static::query()
            ->orderBy('position')
            ->orderBy('nom')
            ->pluck('nom', 'slug')
            ->all();
    }

    public static function makeSlug(string $nom): string
    {
        $base = Str::slug($nom, '_');
        if ($base === '') {
            $base = 'categorie';
        }

        $slug = $base;
        $i = 2;
        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base.'_'.$i;
            $i++;
        }

        return $slug;
    }
}
