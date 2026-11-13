<?php

namespace App\Support;

use App\Models\Departement;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class NavbarMenu
{
    /** @var array<int, array<string, bool>>|null */
    private static ?array $cache = null;

    /**
     * Sidebar entries that can be shown or hidden per department.
     *
     * @return list<array{key:string,label:string,group:string,permission:string,routes:list<string>}>
     */
    public static function items(): array
    {
        return [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'group' => 'Général', 'permission' => 'dashboard.view', 'routes' => ['dashboard']],
            ['key' => 'discussions', 'label' => 'Discussion', 'group' => 'Général', 'permission' => 'discussions.view', 'routes' => ['discussions.*']],
            ['key' => 'activite', 'label' => 'Activité', 'group' => 'Général', 'permission' => 'activite.view', 'routes' => ['activite.*']],
            ['key' => 'budget_annuel', 'label' => 'Budget annuel', 'group' => 'Budget & Dépenses', 'permission' => 'budget_annuel.view', 'routes' => ['budget-annuels.*']],
            ['key' => 'budget_mensuel', 'label' => 'Budget mensuel', 'group' => 'Budget & Dépenses', 'permission' => 'budget_mensuel.view', 'routes' => ['budgets.*']],
            ['key' => 'depenses', 'label' => 'Dépenses', 'group' => 'Budget & Dépenses', 'permission' => 'depenses.view', 'routes' => ['depenses.*']],
            ['key' => 'fournisseurs', 'label' => 'Fournisseurs', 'group' => 'Budget & Dépenses', 'permission' => 'fournisseurs.view', 'routes' => ['fournisseurs.*']],
            ['key' => 'gestion_projet', 'label' => 'Gestion de projet', 'group' => 'Budget & Dépenses', 'permission' => 'gestion_projet.view', 'routes' => ['gestion-projet.*']],
            ['key' => 'campagnes', 'label' => 'Campagnes (Boost FB)', 'group' => 'Campagnes', 'permission' => 'campagnes.view', 'routes' => ['campagnes.*']],
            ['key' => 'calendrier_editorial', 'label' => 'Calendrier ESCM', 'group' => 'Campagnes', 'permission' => 'calendrier_editorial.view', 'routes' => ['calendrier-editorial', 'calendrier-editorial.*']],
            ['key' => 'calendrier_saan', 'label' => 'Calendrier SAAN', 'group' => 'Campagnes', 'permission' => 'calendrier_saan.view', 'routes' => ['calendrier-saan', 'calendrier-saan.*']],
            ['key' => 'calendrier_krismasy', 'label' => 'Calendrier Krismasy', 'group' => 'Campagnes', 'permission' => 'calendrier_krismasy.view', 'routes' => ['calendrier-krismasy', 'calendrier-krismasy.*']],
            ['key' => 'evenements', 'label' => 'Événements', 'group' => 'Campagnes', 'permission' => 'evenements.view', 'routes' => ['evenements.*']],
            ['key' => 'suivi_liens', 'label' => 'Suivi de lien', 'group' => 'Suivi de lien', 'permission' => 'suivi_liens.view', 'routes' => ['suivi-liens.*']],
            ['key' => 'crm', 'label' => 'CRM', 'group' => 'CRM', 'permission' => 'crm.view', 'routes' => ['crm.*']],
            ['key' => 'online_tests', 'label' => 'Test en ligne', 'group' => 'Test en ligne', 'permission' => 'online_tests.view', 'routes' => ['crm.online-tests.*']],
            ['key' => 'stocks', 'label' => 'Stocks', 'group' => 'Stock Marketing', 'permission' => 'stocks.view', 'routes' => ['stocks.index', 'stocks.create', 'stocks.store', 'stocks.edit', 'stocks.update', 'stocks.destroy']],
            ['key' => 'stocks_mouvements', 'label' => 'Entrées / Sorties', 'group' => 'Stock Marketing', 'permission' => 'stocks_mouvements.view', 'routes' => ['stocks.mouvements.*']],
            ['key' => 'it_ticketing', 'label' => 'IT Ticketing', 'group' => 'IT Ticketing', 'permission' => 'it_ticketing.view', 'routes' => ['it-tickets.*']],
            ['key' => 'enseignants', 'label' => 'CV enseignant', 'group' => 'CV enseignant', 'permission' => 'enseignants.view', 'routes' => ['enseignants.*']],
            ['key' => 'ecole_dashboard', 'label' => 'Dashboard', 'group' => 'Gestion École', 'permission' => 'ecole.view', 'routes' => ['ecole.dashboard']],
            ['key' => 'ecole_etudiants', 'label' => 'Gestion Etudiants', 'group' => 'Gestion École', 'permission' => 'ecole.view', 'routes' => ['ecole.etudiants.*']],
            ['key' => 'ecole_classes', 'label' => 'Gestion Classe', 'group' => 'Gestion École', 'permission' => 'ecole.view', 'routes' => ['ecole.classes.*']],
            ['key' => 'ecole_groupes', 'label' => 'Gestion Groupe', 'group' => 'Gestion École', 'permission' => 'ecole.view', 'routes' => ['ecole.groupes.*']],
            ['key' => 'ecole_emplois', 'label' => 'Emplois du temps', 'group' => 'Gestion École', 'permission' => 'ecole.view', 'routes' => ['ecole.emplois.*']],
            ['key' => 'ecole_absences', 'label' => 'Retard et Absence', 'group' => 'Gestion École', 'permission' => 'ecole.view', 'routes' => ['ecole.absences.*']],
            ['key' => 'ecole_bulletins', 'label' => 'Bulletin de note', 'group' => 'Gestion École', 'permission' => 'ecole.view', 'routes' => ['ecole.bulletins.*']],
            ['key' => 'ecole_parametres', 'label' => 'Paramètre école', 'group' => 'Gestion École', 'permission' => 'ecole.view', 'routes' => ['ecole.parametres.*', 'ecole.annee']],
        ];
    }

    /**
     * @return list<string>
     */
    public static function schoolKeys(): array
    {
        return array_values(array_map(
            fn (array $item) => $item['key'],
            array_filter(static::items(), fn (array $item) => $item['group'] === 'Gestion École')
        ));
    }

    public static function enabledByDefault(Departement $departement, string $key): bool
    {
        if (in_array($key, static::schoolKeys(), true)) {
            return $departement->slug === 'pedagogique';
        }

        return true;
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_column(static::items(), 'key');
    }

    /**
     * @return array<string, array{key:string,label:string,group:string,permission:string,routes:list<string>}>
     */
    public static function itemsByKey(): array
    {
        $out = [];
        foreach (static::items() as $item) {
            $out[$item['key']] = $item;
        }

        return $out;
    }

    public static function flush(): void
    {
        static::$cache = null;
    }

    public static function enabled(?int $departementId, string $key): bool
    {
        if (! in_array($key, static::keys(), true)) {
            return true;
        }

        if (! $departementId || ! Schema::hasTable('departement_menus')) {
            return ! in_array($key, static::schoolKeys(), true);
        }

        $map = static::mapFor($departementId);
        if (! array_key_exists($key, $map)) {
            if (! in_array($key, static::schoolKeys(), true)) {
                return true;
            }

            return Departement::query()->whereKey($departementId)->value('slug') === 'pedagogique';
        }

        return (bool) $map[$key];
    }

    public static function enabledForCurrent(string $key): bool
    {
        $user = auth()->user();

        return static::enabled($user?->currentDepartementId(), $key);
    }

    public static function visible(string $key): bool
    {
        $user = auth()->user();
        if (! $user || ! static::enabledForCurrent($key)) {
            return false;
        }

        $permission = static::itemsByKey()[$key]['permission'] ?? '';

        return $permission === '' || $user->canAccess($permission);
    }

    public static function keyForRoute(?string $routeName): ?string
    {
        if (! $routeName) {
            return null;
        }

        $match = null;
        $score = -1;

        foreach (static::items() as $item) {
            foreach ($item['routes'] as $pattern) {
                if (! Str::is($pattern, $routeName)) {
                    continue;
                }

                $specificity = strlen($pattern);
                if ($specificity > $score) {
                    $score = $specificity;
                    $match = $item['key'];
                }
            }
        }

        return $match;
    }

    public static function routeAllowed(?int $departementId, ?string $routeName): bool
    {
        $key = static::keyForRoute($routeName);
        if (! $key) {
            return true;
        }

        return static::enabled($departementId, $key);
    }

    public static function enableAll(Departement $departement): void
    {
        if (! Schema::hasTable('departement_menus')) {
            return;
        }

        $now = now();
        foreach (static::keys() as $key) {
            $departement->menus()->updateOrCreate(
                ['menu_key' => $key],
                ['enabled' => static::enabledByDefault($departement, $key), 'updated_at' => $now]
            );
        }

        static::flush();
    }

    /**
     * @param  list<string>  $enabledKeys
     */
    public static function sync(Departement $departement, array $enabledKeys): void
    {
        $enabledKeys = array_values(array_intersect(static::keys(), $enabledKeys));
        $now = now();

        foreach (static::keys() as $key) {
            $departement->menus()->updateOrCreate(
                ['menu_key' => $key],
                ['enabled' => in_array($key, $enabledKeys, true), 'updated_at' => $now]
            );
        }

        static::flush();
    }

    /**
     * @return array<string, bool>
     */
    private static function mapFor(int $departementId): array
    {
        if (static::$cache === null) {
            static::$cache = [];
        }

        if (! array_key_exists($departementId, static::$cache)) {
            static::$cache[$departementId] = \App\Models\DepartementMenu::query()
                ->where('departement_id', $departementId)
                ->pluck('enabled', 'menu_key')
                ->map(fn ($enabled) => (bool) $enabled)
                ->all();
        }

        return static::$cache[$departementId];
    }
}
