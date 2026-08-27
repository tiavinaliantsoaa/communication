<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmCandidate;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $total = CrmCandidate::count();
        $abandons = CrmCandidate::where('abandon', true)->count();
        $actifs = max(0, $total - $abandons);
        $nouveauxMois = CrmCandidate::where('created_at', '>=', now()->startOfMonth())->count();
        $inscrits = CrmCandidate::where('abandon', false)->where('statut', 'inscrit')->count();
        $prospects = CrmCandidate::where('abandon', false)->where('statut', 'prospect')->count();
        $conversion = $actifs > 0 ? round(($inscrits / $actifs) * 100, 1) : 0;

        $kpis = [
            'total' => $total,
            'nouveaux_mois' => $nouveauxMois,
            'inscrits' => $inscrits,
            'conversion' => $conversion,
            'prospects' => $prospects,
            'abandons' => $abandons,
        ];

        $months = collect(range(11, 0))->map(fn ($i) => now()->subMonths($i)->startOfMonth());
        $driver = DB::getDriverName();
        $ymExpr = $driver === 'sqlite'
            ? "strftime('%Y-%m', created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m')";

        $monthlyRaw = CrmCandidate::query()
            ->select(DB::raw("{$ymExpr} as ym"), DB::raw('COUNT(*) as total'))
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $chartMonthly = [
            'labels' => $months->map(fn ($m) => $m->translatedFormat('M Y'))->values()->all(),
            'series' => $months->map(fn ($m) => (int) ($monthlyRaw[$m->format('Y-m')] ?? 0))->values()->all(),
        ];

        $byStatus = CrmCandidate::query()
            ->select('statut', DB::raw('COUNT(*) as total'))
            ->where('abandon', false)
            ->groupBy('statut')
            ->pluck('total', 'statut');

        $chartStatus = [
            'labels' => collect(CrmCandidate::STATUTS)->map(fn ($label, $key) => $label)->values()->all(),
            'series' => collect(CrmCandidate::STATUTS)->map(fn ($label, $key) => (int) ($byStatus[$key] ?? 0))->values()->all(),
        ];

        $funnelCounts = collect(CrmCandidate::FUNNEL_STATUTS)->mapWithKeys(function ($key) {
            return [$key => CrmCandidate::where('abandon', false)->where('statut', $key)->count()];
        });

        $chartFunnel = [
            'labels' => collect(CrmCandidate::FUNNEL_STATUTS)->map(fn ($k) => CrmCandidate::STATUTS[$k])->values()->all(),
            'series' => $funnelCounts->values()->all(),
        ];

        $chartByAdvisor = $this->chartCountsByAdvisor(
            CrmCandidate::query()
                ->select('advisor_id', DB::raw('COUNT(*) as total'))
                ->where('abandon', false)
                ->groupBy('advisor_id')
                ->get()
        );

        $chartInscritsByAdvisor = $this->chartCountsByAdvisor(
            CrmCandidate::query()
                ->select('advisor_id', DB::raw('COUNT(*) as total'))
                ->where('abandon', false)
                ->where('statut', 'inscrit')
                ->groupBy('advisor_id')
                ->get()
        );

        $programmeExpr = "COALESCE(NULLIF(TRIM(programme), ''), 'Sans programme')";
        $byProgramme = CrmCandidate::query()
            ->select(DB::raw("{$programmeExpr} as programme_label"), DB::raw('COUNT(*) as total'))
            ->where('abandon', false)
            ->groupBy(DB::raw($programmeExpr))
            ->orderByDesc('total')
            ->get();

        $topProgrammes = $byProgramme->take(10);
        $autres = (int) $byProgramme->skip(10)->sum('total');
        if ($autres > 0) {
            $topProgrammes = $topProgrammes->concat(collect([
                (object) ['programme_label' => 'Autres', 'total' => $autres],
            ]));
        }

        $chartByProgramme = [
            'labels' => $topProgrammes->pluck('programme_label')->map(function ($label) {
                $label = (string) $label;

                return mb_strlen($label) > 28 ? mb_substr($label, 0, 27).'…' : $label;
            })->values()->all(),
            'series' => $topProgrammes->pluck('total')->map(fn ($n) => (int) $n)->values()->all(),
        ];

        $recent = CrmCandidate::with('advisor')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('crm.dashboard', compact(
            'kpis',
            'chartMonthly',
            'chartStatus',
            'chartFunnel',
            'chartByAdvisor',
            'chartInscritsByAdvisor',
            'chartByProgramme',
            'recent'
        ));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object{advisor_id: mixed, total: mixed}>  $rows
     * @return array{labels: list<string>, series: list<int>}
     */
    private function chartCountsByAdvisor($rows): array
    {
        if ($rows->isEmpty()) {
            return ['labels' => [], 'series' => []];
        }

        $ids = $rows->pluck('advisor_id')->filter()->unique()->values();
        $names = $ids->isEmpty()
            ? collect()
            : User::query()->whereIn('id', $ids)->pluck('name', 'id');

        $sorted = $rows->sortByDesc(fn ($row) => (int) $row->total)->values();

        return [
            'labels' => $sorted->map(function ($row) use ($names) {
                if ($row->advisor_id === null || $row->advisor_id === '') {
                    return 'Non assigné';
                }

                return $names[$row->advisor_id] ?? 'Utilisateur #'.$row->advisor_id;
            })->all(),
            'series' => $sorted->map(fn ($row) => (int) $row->total)->all(),
        ];
    }
}
