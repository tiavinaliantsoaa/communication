<?php

namespace App\Http\Controllers;

use App\Models\BudgetAnnuel;
use App\Models\Campagne;
use App\Models\Depense;
use App\Models\Stock;
use App\Services\AlerteService;
use App\Services\BudgetMensuelService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, AlerteService $alerteService, BudgetMensuelService $budgetMensuel)
    {
        $annee = (int) $request->get('annee', now()->year);
        $mois = (int) $request->get('mois', now()->month);
        if ($mois < 1 || $mois > 12) {
            $mois = (int) now()->month;
        }
        if ($annee < 2020 || $annee > 2100) {
            $annee = (int) now()->year;
        }

        $snap = $budgetMensuel->forMonth($annee, $mois);

        $budgetAnnuel = BudgetAnnuel::where('annee', $annee)->first();
        $budgetAnnuelMontant = $budgetAnnuel ? (float) $budgetAnnuel->montant : 0;
        $budgetAnnuelAlloue = (float) \App\Models\Budget::where('annee', $annee)->sum('montant');
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

    private function buildEvolutionChart(int $annee, BudgetMensuelService $budgetMensuel): array
    {
        $moisLabels = ['Janv.', 'Févr.', 'Mars', 'Avr.', 'Mai', 'Juin', 'Juil.', 'Août', 'Sept.', 'Oct.', 'Nov.', 'Déc.'];
        $budgetPrevu = [];
        $depenses = [];
        $reste = [];

        for ($m = 1; $m <= 12; $m++) {
            $snap = $budgetMensuel->forMonth($annee, $m);
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

    private function buildRepartitionChart($depensesMois, float $total): array
    {
        $categories = [
            'sponsoring_reseaux' => ['label' => 'Boost Facebook', 'color' => '#3b82f6'],
            'production_contenu' => ['label' => 'Production contenu', 'color' => '#8b5cf6'],
            'impression' => ['label' => 'Impression', 'color' => '#f97316'],
            'goodies_evenements' => ['label' => 'Goodies / Événements', 'color' => '#22c55e'],
        ];

        $data = [];
        foreach ($categories as $key => $meta) {
            $montant = (float) $depensesMois->where('categorie', $key)->sum('montant');
            $data[] = [
                'label' => $meta['label'],
                'montant' => $montant,
                'color' => $meta['color'],
                'pct' => $total > 0 ? round(($montant / $total) * 100) : 0,
            ];
        }

        return ['items' => $data, 'total' => $total];
    }
}
