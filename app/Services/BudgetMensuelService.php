<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Depense;

class BudgetMensuelService
{
    /**
     * Snapshot of a monthly budget including carry-over from previous overspend.
     *
     * @return array{
     *     annee: int,
     *     mois: int,
     *     budget_base: float,
     *     report_precedent: float,
     *     budget_effectif: float,
     *     depense: float,
     *     reste: float,
     *     depassement: float,
     *     pct_utilise: float,
     *     is_depassement: bool
     * }
     */
    public function forMonth(int $annee, int $mois, ?int $departementId = null): array
    {
        $carry = $this->carryInto($annee, $mois, $departementId);

        return $this->compute($annee, $mois, $carry, $departementId);
    }

    /**
     * Amount of previous months' overspend deducted from this month's budget.
     */
    public function carryInto(int $annee, int $mois, ?int $departementId = null): float
    {
        $carry = 0.0;

        // Walk the previous year then current year months before target,
        // so December overspend carries into January.
        for ($y = $annee - 1; $y <= $annee; $y++) {
            $lastMonth = ($y === $annee) ? ($mois - 1) : 12;
            for ($m = 1; $m <= $lastMonth; $m++) {
                $snap = $this->compute($y, $m, $carry, $departementId);
                $carry = $snap['depassement'];
            }
        }

        return $carry;
    }

    /**
     * @return array{
     *     annee: int,
     *     mois: int,
     *     budget_base: float,
     *     report_precedent: float,
     *     budget_effectif: float,
     *     depense: float,
     *     reste: float,
     *     depassement: float,
     *     pct_utilise: float,
     *     is_depassement: bool
     * }
     */
    private function compute(int $annee, int $mois, float $reportPrecedent, ?int $departementId = null): array
    {
        $budgetQuery = $departementId
            ? Budget::withoutGlobalScope('departement')->where('departement_id', $departementId)
            : Budget::query();
        $depenseQuery = $departementId
            ? Depense::withoutGlobalScope('departement')->where('departement_id', $departementId)
            : Depense::query();

        $budget = $budgetQuery->where('annee', $annee)->where('mois', $mois)->first();
        $budgetBase = $budget ? (float) $budget->montant : 0.0;
        $depense = (float) $depenseQuery->whereYear('date_depense', $annee)
            ->whereMonth('date_depense', $mois)
            ->sum('montant');

        $budgetEffectif = $budgetBase - $reportPrecedent;
        $reste = $budgetEffectif - $depense;
        $depassement = max(0.0, -$reste);
        $basePourPct = $budgetEffectif > 0 ? $budgetEffectif : ($budgetBase > 0 ? $budgetBase : 0.0);
        $pctUtilise = $basePourPct > 0 ? round(($depense / $basePourPct) * 100) : ($depense > 0 ? 100 : 0);

        return [
            'annee' => $annee,
            'mois' => $mois,
            'budget_base' => $budgetBase,
            'report_precedent' => $reportPrecedent,
            'budget_effectif' => $budgetEffectif,
            'depense' => $depense,
            'reste' => $reste,
            'depassement' => $depassement,
            'pct_utilise' => $pctUtilise,
            'is_depassement' => $reste < 0,
        ];
    }
}
