<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = [
        'key',
        'label',
        'group',
        'position',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Catalog of all app permissions (pages + actions).
     *
     * @return array<int, array{key:string,label:string,group:string,position:int}>
     */
    public static function catalog(): array
    {
        $groups = [
            'Dashboard' => [
                'dashboard.view' => 'Voir le dashboard',
            ],
            'Statistiques' => [
                'statistiques.view' => 'Voir les statistiques',
            ],
            'Activité' => [
                'activite.view' => 'Voir l’activité globale',
            ],
            'Budget annuel' => [
                'budget_annuel.view' => 'Voir',
                'budget_annuel.create' => 'Créer',
                'budget_annuel.update' => 'Modifier',
                'budget_annuel.delete' => 'Supprimer',
            ],
            'Budget mensuel' => [
                'budget_mensuel.view' => 'Voir',
                'budget_mensuel.create' => 'Créer',
                'budget_mensuel.update' => 'Modifier',
                'budget_mensuel.delete' => 'Supprimer',
            ],
            'Dépenses' => [
                'depenses.view' => 'Voir',
                'depenses.create' => 'Créer',
                'depenses.update' => 'Modifier',
                'depenses.delete' => 'Supprimer',
                'depenses.approve' => 'Approuver',
            ],
            'Fournisseurs' => [
                'fournisseurs.view' => 'Voir',
                'fournisseurs.create' => 'Créer',
                'fournisseurs.update' => 'Modifier',
                'fournisseurs.delete' => 'Supprimer',
            ],
            'Gestion de projet' => [
                'gestion_projet.view' => 'Voir',
                'gestion_projet.create' => 'Créer / ajouter',
                'gestion_projet.update' => 'Modifier',
                'gestion_projet.delete' => 'Supprimer',
            ],
            'Campagnes Boost FB' => [
                'campagnes.view' => 'Voir',
                'campagnes.create' => 'Créer',
                'campagnes.update' => 'Modifier',
                'campagnes.delete' => 'Supprimer',
            ],
            'Suivi de lien' => [
                'suivi_liens.view' => 'Voir',
                'suivi_liens.create' => 'Créer',
                'suivi_liens.update' => 'Modifier',
                'suivi_liens.delete' => 'Supprimer',
            ],
            'Calendrier éditorial' => [
                'calendrier_editorial.view' => 'Voir',
                'calendrier_editorial.create' => 'Créer',
                'calendrier_editorial.update' => 'Modifier',
                'calendrier_editorial.delete' => 'Supprimer',
                'calendrier_editorial.validate' => 'Valider',
            ],
            'Événements' => [
                'evenements.view' => 'Voir',
                'evenements.create' => 'Créer',
                'evenements.update' => 'Modifier',
                'evenements.delete' => 'Supprimer',
            ],
            'Stocks' => [
                'stocks.view' => 'Voir',
                'stocks.create' => 'Créer',
                'stocks.update' => 'Modifier',
                'stocks.delete' => 'Supprimer',
            ],
            'Entrées / Sorties' => [
                'stocks_mouvements.view' => 'Voir',
                'stocks_mouvements.create' => 'Créer',
                'stocks_mouvements.update' => 'Modifier',
                'stocks_mouvements.delete' => 'Supprimer',
            ],
            'Utilisateurs' => [
                'users.view' => 'Voir',
                'users.create' => 'Créer',
                'users.update' => 'Modifier',
                'users.delete' => 'Supprimer',
            ],
            'Gestion d’accès' => [
                'acces.manage' => 'Gérer les rôles et accès',
            ],
            'Paramètres' => [
                'parametres.systeme' => 'Configuration système',
            ],
        ];

        $out = [];
        $pos = 0;
        foreach ($groups as $group => $items) {
            foreach ($items as $key => $label) {
                $out[] = [
                    'key' => $key,
                    'label' => $label,
                    'group' => $group,
                    'position' => $pos++,
                ];
            }
        }

        return $out;
    }

    public static function syncCatalog(): void
    {
        foreach (static::catalog() as $row) {
            static::updateOrCreate(
                ['key' => $row['key']],
                [
                    'label' => $row['label'],
                    'group' => $row['group'],
                    'position' => $row['position'],
                ]
            );
        }
    }
}
