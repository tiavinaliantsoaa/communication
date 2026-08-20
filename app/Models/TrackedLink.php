<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TrackedLink extends Model
{
    protected $fillable = [
        'user_id',
        'nom',
        'destination_url',
        'slug',
        'clicks_count',
        'actif',
    ];

    protected $casts = [
        'clicks_count' => 'integer',
        'actif' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(TrackedLinkVisit::class)->latest('created_at');
    }

    public function getShortUrlAttribute(): string
    {
        return url('/l/'.$this->slug);
    }

    public static function generateUniqueSlug(?string $preferred = null): string
    {
        $base = $preferred !== null && $preferred !== ''
            ? Str::slug($preferred)
            : Str::lower(Str::random(8));

        if ($base === '') {
            $base = Str::lower(Str::random(8));
        }

        $slug = $base;
        $i = 1;
        while (static::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
