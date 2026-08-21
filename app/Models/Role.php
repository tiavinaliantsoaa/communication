<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Role extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'home_route',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    /**
     * Selectable startup pages after login (route name => meta).
     *
     * @return array<string, array{label:string,permission:string}>
     */
    public static function homePageOptions(): array
    {
        return [
            'dashboard' => ['label' => 'Dashboard général', 'permission' => 'dashboard.view'],
            'statistiques' => ['label' => 'Statistiques', 'permission' => 'statistiques.view'],
            'activite.index' => ['label' => 'Activité', 'permission' => 'activite.view'],
            'budget-annuels.index' => ['label' => 'Budget annuel', 'permission' => 'budget_annuel.view'],
            'budgets.index' => ['label' => 'Budget mensuel', 'permission' => 'budget_mensuel.view'],
            'depenses.index' => ['label' => 'Dépenses', 'permission' => 'depenses.view'],
            'fournisseurs.index' => ['label' => 'Fournisseurs', 'permission' => 'fournisseurs.view'],
            'gestion-projet.index' => ['label' => 'Gestion de projet', 'permission' => 'gestion_projet.view'],
            'campagnes.index' => ['label' => 'Campagnes (Boost FB)', 'permission' => 'campagnes.view'],
            'calendrier-editorial' => ['label' => 'Calendrier éditorial', 'permission' => 'calendrier_editorial.view'],
            'evenements.index' => ['label' => 'Événements', 'permission' => 'evenements.view'],
            'suivi-liens.index' => ['label' => 'Suivi de lien', 'permission' => 'suivi_liens.view'],
            'crm.dashboard' => ['label' => 'CRM — Tableau de bord', 'permission' => 'crm.view'],
            'crm.candidats.index' => ['label' => 'CRM — Candidats', 'permission' => 'crm.view'],
            'crm.pipeline' => ['label' => 'CRM — Pipeline', 'permission' => 'crm.view'],
            'stocks.index' => ['label' => 'Stocks', 'permission' => 'stocks.view'],
            'stocks.mouvements.index' => ['label' => 'Entrées / Sorties', 'permission' => 'stocks_mouvements.view'],
            'users.index' => ['label' => 'Utilisateurs', 'permission' => 'users.view'],
            'profile.edit' => ['label' => 'Profil', 'permission' => ''],
        ];
    }

    public function getHomePageLabelAttribute(): string
    {
        if (! $this->home_route) {
            return 'Dashboard général (défaut)';
        }

        return self::homePageOptions()[$this->home_route]['label'] ?? $this->home_route;
    }
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users()
    {
        return User::where('role', $this->slug);
    }

    public static function makeSlug(string $name): string
    {
        $base = Str::slug($name, '_');
        if ($base === '') {
            $base = 'role';
        }

        $slug = $base;
        $i = 1;
        while (static::where('slug', $slug)->exists()) {
            $slug = $base.'_'.$i++;
        }

        return $slug;
    }
}
