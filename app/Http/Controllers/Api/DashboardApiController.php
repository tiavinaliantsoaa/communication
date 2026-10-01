<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\BudgetAnnuel;
use App\Models\Campagne;
use App\Services\AlerteService;
use App\Services\BudgetMensuelService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardApiController extends Controller
{
    public function __invoke(Request $request, AlerteService $alerteService, BudgetMensuelService $budgetMensuel)
    {
        [$annee, $mois] = \App\Support\Periode::current();

        $snap = $budgetMensuel->forMonth($annee, $mois);

        $budgetAnnuel = BudgetAnnuel::where('annee', $annee)->first();
        $budgetAnnuelMontant = $budgetAnnuel ? (float) $budgetAnnuel->montant : 0;
        $budgetAnnuelAlloue = (float) Budget::where('annee', $annee)->sum('montant');

        $alertes = $alerteService->all($annee, $mois);

        return response()->json([
            'periode' => [
                'annee' => $annee,
                'mois' => $mois,
                'label' => Carbon::create($annee, $mois, 1)->locale('fr')->isoFormat('MMMM YYYY'),
            ],
            'kpis' => [
                'budget_annuel' => $budgetAnnuelMontant,
                'budget_annuel_alloue' => $budgetAnnuelAlloue,
                'budget_mensuel' => $snap['budget_base'],
                'budget_effectif' => $snap['budget_effectif'],
                'report_precedent' => $snap['report_precedent'],
                'depense' => $snap['depense'],
                'reste' => $snap['reste'],
                'depassement' => $snap['depassement'],
                'is_depassement' => $snap['is_depassement'],
                'pct_utilise' => $snap['pct_utilise'],
                'nb_operations' => \App\Models\Depense::whereYear('date_depense', $annee)->whereMonth('date_depense', $mois)->count(),
                'nb_campagnes' => Campagne::where('statut', 'active')->count(),
                'nb_alertes' => count($alertes),
            ],
            'alertes' => collect($alertes)->take(8)->values(),
        ]);
    }
}
