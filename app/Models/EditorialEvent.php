<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class EditorialEvent extends Model
{
    public const MAX_VISUELS = 10;

    public const SERVICE_EDITORIAL = 'editorial';

    public const SERVICE_SAAN = 'saan';

    public const SERVICE_KRISMASY = 'krismasy';

    public const SERVICES = [
        self::SERVICE_EDITORIAL => [
            'label' => 'Calendrier ESCM',
            'subtitle' => 'Planification des contenus et actions de communication ESCM',
            'route_prefix' => 'calendrier-editorial',
            'permission_prefix' => 'calendrier_editorial',
            'module' => 'editorial',
        ],
        self::SERVICE_SAAN => [
            'label' => 'Calendrier SAAN',
            'subtitle' => 'Planification des contenus et actions SAAN',
            'route_prefix' => 'calendrier-saan',
            'permission_prefix' => 'calendrier_saan',
            'module' => 'calendrier_saan',
        ],
        self::SERVICE_KRISMASY => [
            'label' => 'Calendrier Krismasy',
            'subtitle' => 'Planification des contenus et actions Krismasy',
            'route_prefix' => 'calendrier-krismasy',
            'permission_prefix' => 'calendrier_krismasy',
            'module' => 'calendrier_krismasy',
        ],
    ];

    protected $fillable = [
        'service',
        'titre',
        'categorie',
        'type_contenu',
        'booster',
        'date_debut',
        'date_fin',
        'statut',
        'valide',
        'texte_publication',
        'texte_publication_linkedin',
        'visuel_path',
        'visuel_nom',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'booster' => 'boolean',
        'valide' => 'boolean',
    ];

    public const CATEGORIES = [
        'facebook' => ['label' => 'Facebook', 'color_name' => 'Bleu', 'color' => '#1877F2', 'text' => '#ffffff'],
        'instagram' => ['label' => 'Instagram', 'color_name' => 'Rose', 'color' => '#E1306C', 'text' => '#ffffff'],
        'linkedin' => ['label' => 'LinkedIn', 'color_name' => 'Bleu foncé', 'color' => '#004182', 'text' => '#ffffff'],
        'tiktok' => ['label' => 'Tik Tok', 'color_name' => 'Noir', 'color' => '#111827', 'text' => '#ffffff'],
        'befiana_sms' => ['label' => 'Befiana SMS', 'color_name' => 'Vert', 'color' => '#16a34a', 'text' => '#ffffff'],
        'brevo' => ['label' => 'Brevo', 'color_name' => 'Violet', 'color' => '#7c3aed', 'text' => '#ffffff'],
        'article_site_web' => ['label' => 'Article site web', 'color_name' => 'Cyan', 'color' => '#0891b2', 'text' => '#ffffff'],
    ];

    public const TYPES_CONTENU = [
        'FI' => 'FI',
        'FP' => 'FP',
    ];

    public function visuels(): HasMany
    {
        return $this->hasMany(EditorialEventVisuel::class)->orderBy('position')->orderBy('id');
    }

    public function scopeForService($query, string $service)
    {
        return $query->where('service', $service);
    }

    public static function serviceMeta(string $service): array
    {
        return self::SERVICES[$service] ?? self::SERVICES[self::SERVICE_EDITORIAL];
    }

    public static function serviceFromRouteName(?string $routeName): string
    {
        if (! $routeName) {
            return self::SERVICE_EDITORIAL;
        }

        foreach (self::SERVICES as $service => $meta) {
            $prefix = $meta['route_prefix'];
            if ($routeName === $prefix || str_starts_with($routeName, $prefix.'.')) {
                return $service;
            }
        }

        return self::SERVICE_EDITORIAL;
    }

    protected function categorie(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (is_array($value)) {
                    return array_values($value);
                }

                $decoded = json_decode((string) $value, true);
                if (is_array($decoded)) {
                    return array_values($decoded);
                }

                return $value ? [(string) $value] : [];
            },
            set: function ($value) {
                $list = is_array($value) ? $value : [$value];
                $list = array_values(array_unique(array_filter($list)));

                return json_encode($list);
            },
        );
    }

    public function getCategoriesMetaAttribute(): array
    {
        return collect($this->categorie)
            ->map(fn ($key) => array_merge(
                ['key' => $key],
                self::CATEGORIES[$key] ?? ['label' => $key, 'color' => '#94a3b8', 'text' => '#ffffff']
            ))
            ->values()
            ->all();
    }

    public function getCategorieMetaAttribute(): array
    {
        return $this->categories_meta[0] ?? [
            'key' => null,
            'label' => '—',
            'color' => '#94a3b8',
            'text' => '#ffffff',
        ];
    }

    public function getCategoriesLabelAttribute(): string
    {
        return collect($this->categories_meta)->pluck('label')->implode(', ');
    }

    public function getIsFacebookFiAttribute(): bool
    {
        return in_array('facebook', $this->categorie ?? [], true) && $this->type_contenu === 'FI';
    }

    /** @deprecated Prefer visuels relation; kept for legacy single-image fields. */
    public function getVisuelUrlAttribute(): ?string
    {
        $first = $this->relationLoaded('visuels')
            ? $this->visuels->first()
            : $this->visuels()->first();

        if ($first) {
            return $first->url;
        }

        if (! $this->visuel_path) {
            return null;
        }

        return Storage::disk('public')->url($this->visuel_path);
    }

    protected static function booted(): void
    {
        static::deleting(function (EditorialEvent $event) {
            $event->visuels()->each(fn (EditorialEventVisuel $v) => $v->delete());

            if ($event->visuel_path) {
                Storage::disk('public')->delete($event->visuel_path);
            }
        });
    }
}
