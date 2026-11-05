<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\BudgetAnnuel;
use App\Models\Campagne;
use App\Models\Departement;
use App\Models\Depense;
use App\Models\DepenseCategorie;
use App\Models\Stock;
use App\Services\AlerteService;
use App\Services\BudgetMensuelService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, AlerteService $alerteService, BudgetMensuelService $budgetMensuel)
    {
        if (! $request->user()?->canAccess('dashboard.view')) {
            return redirect()->to($request->user()->homeUrl());
        }

        $actif = Departement::query()->find($request->user()->currentDepartementId());
        if ($actif?->slug === 'direction') {
            return $this->directionIndex($request, $alerteService, $budgetMensuel);
        }

        [$annee, $mois] = \App\Support\Periode::current();

        $snap = $budgetMensuel->forMonth($annee, $mois);

        $budgetAnnuel = BudgetAnnuel::where('annee', $annee)->first();
        $budgetAnnuelMontant = $budgetAnnuel ? (float) $budgetAnnuel->montant : 0;
        $budgetAnnuelAlloue = (float) Budget::where('annee', $annee)->sum('montant');
        $budgetAnnuelRestant = max(0, $budgetAnnuelMontant - $budgetAnnuelAlloue);
        $pctAnnuelAlloue = $budgetAnnuelMontant > 0
            ? (int) min(100, round(($budgetAnnuelAlloue / $budgetAnnuelMontant) * 100))
            : 0;

        $depensesMois = Depense::whereYear('date_depense', $annee)
            ->whereMonth('date_depense', $mois)
            ->get();

        $totalDepense = $snap['depense'];
        $resteDisponible = $snap['reste'];
        $sponsoringTotal = (float) $depensesMois->where('categorie', 'sponsoring_reseaux')->sum('montant');

        $pctUtilise = $snap['pct_utilise'];
        $pctRestant = $snap['budget_effectif'] > 0
            ? (int) round(($resteDisponible / $snap['budget_effectif']) * 100)
            : ($resteDisponible < 0 ? 0 : 0);
        $pctSponsoring = $snap['budget_effectif'] > 0
            ? (int) round(($sponsoringTotal / $snap['budget_effectif']) * 100)
            : 0;

        $nbOperations = $depensesMois->count();
        $nbCampagnesActives = Campagne::where('statut', 'active')->count();

        $kpis = [
            'budget_annuel' => $budgetAnnuelMontant,
            'budget_annuel_alloue' => $budgetAnnuelAlloue,
            'budget_annuel_restant' => $budgetAnnuelRestant,
            'pct_annuel_alloue' => $pctAnnuelAlloue,
            'budget_mensuel' => $snap['budget_base'],
            'budget_effectif' => $snap['budget_effectif'],
            'report_precedent' => $snap['report_precedent'],
            'depense' => $totalDepense,
            'reste' => $resteDisponible,
            'sponsoring' => $sponsoringTotal,
            'pct_utilise' => $pctUtilise,
            'pct_restant' => $pctRestant,
            'pct_sponsoring' => $pctSponsoring,
            'nb_operations' => $nbOperations,
            'nb_campagnes' => $nbCampagnesActives,
            'is_depassement' => $snap['is_depassement'],
            'depassement' => $snap['depassement'],
        ];

        $chartEvolution = $this->buildEvolutionChart($annee, $budgetMensuel);
        $chartRepartition = $this->buildRepartitionChart($depensesMois, $totalDepense);

        $depensesRecentes = Depense::whereYear('date_depense', $annee)
            ->whereMonth('date_depense', $mois)
            ->orderByDesc('date_depense')
            ->limit(8)
            ->get();
        $stocks = Stock::orderBy('article')->limit(5)->get();

        $alertes = $alerteService->all($annee, $mois);
        $nbAlertes = count($alertes);

        $moisLabel = Carbon::create($annee, $mois, 1)->locale('fr')->isoFormat('MMMM YYYY');

        return view('dashboard', compact(
            'kpis',
            'chartEvolution',
            'chartRepartition',
            'depensesRecentes',
            'stocks',
            'alertes',
            'nbAlertes',
            'moisLabel',
            'annee',
            'mois'
        ));
    }

    private function directionIndex(Request $request, AlerteService $alerteService, BudgetMensuelService $budgetMensuel)
    {
        [$annee, $mois] = \App\Support\Periode::current();

        $departements = Departement::query()->orderBy('nom')->get();
        $parSlug = $departements->keyBy('slug');
        $commercial = $parSlug->get('commercial');
        $communication = $parSlug->get('communication');
        $pedagogique = $parSlug->get('pedagogique');

        $depensesMois = Depense::withoutGlobalScope('departement')
            ->whereYear('date_depense', $annee)
            ->whereMonth('date_depense', $mois)
            ->get();

        $chartsEvolution = [
            'tous' => [
                'labels' => $this->moisLabels(),
                'budget_prevu' => array_fill(0, 12, 0.0),
                'depenses' => array_fill(0, 12, 0.0),
                'reste' => array_fill(0, 12, 0.0),
            ],
        ];
        $chartDepartements = [
            ['key' => 'tous', 'label' => 'Tous les départements'],
        ];

        foreach ($departements as $departement) {
            $chart = $this->buildEvolutionChart($annee, $budgetMensuel, $departement->id);
            foreach (['budget_prevu', 'depenses', 'reste'] as $serie) {
                foreach ($chart[$serie] as $index => $valeur) {
                    $chartsEvolution['tous'][$serie][$index] += $valeur;
                }
            }
            if ($departement->slug === 'direction') {
                continue;
            }
            $chartsEvolution[(string) $departement->id] = $chart;
            $chartDepartements[] = ['key' => (string) $departement->id, 'label' => $departement->nom];
        }

        $cartesAnnuelles = [
            array_merge(['label' => 'Tous les départements'], $this->annuelResume($annee)),
            array_merge(['label' => $commercial->nom ?? 'Commercial'], $this->annuelResume($annee, $commercial?->id)),
            array_merge(['label' => $communication->nom ?? 'Communication'], $this->annuelResume($annee, $communication?->id)),
            array_merge(['label' => $pedagogique->nom ?? 'Pédagogique'], $this->annuelResume($annee, $pedagogique?->id)),
        ];

        $chartRepartition = $this->buildRepartitionChart($depensesMois, (float) $depensesMois->sum('montant'), true);
        $depensesRecentes = Depense::withoutGlobalScope('departement')
            ->with('departement')
            ->whereYear('date_depense', $annee)
            ->whereMonth('date_depense', $mois)
            ->orderByDesc('date_depense')
            ->limit(8)
            ->get();
        $stocks = Stock::orderBy('article')->limit(5)->get();

        $alertes = [];
        $includePersonal = true;
        foreach ($departements as $departement) {
            foreach ($alerteService->all($annee, $mois, $request->user(), $departement->id, $includePersonal) as $alerte) {
                if (empty($alerte['perso'])) {
                    $alerte['titre'] = $departement->nom.' — '.$alerte['titre'];
                }
                $alertes[] = $alerte;
            }
            $includePersonal = false;
        }
        $nbAlertes = count($alertes);
        $moisLabel = Carbon::create($annee, $mois, 1)->locale('fr')->isoFormat('MMMM YYYY');
        $vueDirection = true;

        return view('dashboard', compact(
            'cartesAnnuelles',
            'chartsEvolution',
            'chartDepartements',
            'chartRepartition',
            'depensesRecentes',
            'stocks',
            'alertes',
            'nbAlertes',
            'moisLabel',
            'annee',
            'mois',
            'vueDirection'
        ));
    }

    /**
     * @return array{montant: float, restant: float, pct: int}
     */
    private function annuelResume(int $annee, ?int $departementId = null): array
    {
        $annuels = BudgetAnnuel::withoutGlobalScope('departement')->where('annee', $annee);
        $mensuels = Budget::withoutGlobalScope('departement')->where('annee', $annee);
        if ($departementId) {
            $annuels->where('departement_id', $departementId);
            $mensuels->where('departement_id', $departementId);
        }

        $montant = (float) $annuels->sum('montant');
        $alloue = (float) $mensuels->sum('montant');

        return [
            'montant' => $montant,
            'restant' => max(0, $montant - $alloue),
            'pct' => $montant > 0 ? (int) min(100, round(($alloue / $montant) * 100)) : 0,
        ];
    }

    /**
     * @return list<string>
     */
    private function moisLabels(): array
    {
        return ['Janv.', 'Févr.', 'Mars', 'Avr.', 'Mai', 'Juin', 'Juil.', 'Août', 'Sept.', 'Oct.', 'Nov.', 'Déc.'];
    }

    private function buildEvolutionChart(int $annee, BudgetMensuelService $budgetMensuel, ?int $departementId = null): array
    {
        $moisLabels = $this->moisLabels();
        $budgetPrevu = [];
        $depenses = [];
        $reste = [];

        for ($m = 1; $m <= 12; $m++) {
            $snap = $budgetMensuel->forMonth($annee, $m, $departementId);
            $budgetPrevu[] = $snap['budget_effectif'];
            $depenses[] = $snap['depense'];
            $reste[] = $snap['reste'];
        }

        return [
            'labels' => $moisLabels,
            'budget_prevu' => $budgetPrevu,
            'depenses' => $depenses,
            'reste' => $reste,
        ];
    }

    private function buildRepartitionChart($depensesMois, float $total, bool $tousDepartements = false): array
    {
        $palette = ['#3b82f6', '#8b5cf6', '#f97316', '#22c55e', '#ec4899', '#14b8a6', '#eab308', '#64748b'];
        $categories = ($tousDepartements
            ? DepenseCategorie::withoutGlobalScope('departement')
            : DepenseCategorie::query())
            ->orderBy('position')
            ->orderBy('nom')
            ->get();
        if ($tousDepartements) {
            $categories = $categories->unique('slug')->values();
        }
        if ($categories->isEmpty()) {
            $categories = collect(Depense::CATEGORIES)->map(fn ($label, $slug) => (object) [
                'slug' => $slug,
                'nom' => $label,
            ])->values();
        }

        $data = [];
        foreach ($categories as $index => $categorie) {
            $montant = (float) $depensesMois->where('categorie', $categorie->slug)->sum('montant');
            $data[] = [
                'label' => $categorie->nom,
                'montant' => $montant,
                'color' => $palette[$index % count($palette)],
                'pct' => $total > 0 ? round(($montant / $total) * 100) : 0,
            ];
        }

        return ['items' => $data, 'total' => $total];
    }
}
