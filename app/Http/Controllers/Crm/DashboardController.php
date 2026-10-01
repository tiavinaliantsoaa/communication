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

        $sourceCounts = $this->countsBySource();
        $chartFunnel = $this->sourceChart($sourceCounts['all'], $sourceCounts['labels']);
        $sourceMonths = $sourceCounts['months'];
        $sourceSeriesByMonth = $sourceCounts['by_month'];

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
            'sourceMonths',
            'sourceSeriesByMonth',
            'chartByAdvisor',
            'chartInscritsByAdvisor',
            'chartByProgramme',
            'recent'
        ));
    }

    /**
     * @param  \Illuminate\Support\Collection<int|string, int|string>  $counts
     * @param  list<array{key: string, label: string}>  $labels
     * @return array{labels: list<string>, series: list<int>}
     */
    private function sourceChart($counts, array $labels): array
    {
        return [
            'labels' => array_column($labels, 'label'),
            'series' => array_map(fn (array $item) => (int) ($counts[$item['key']] ?? 0), $labels),
        ];
    }

    /**
     * @return array{labels: list<array{key: string, label: string}>, all: \Illuminate\Support\Collection, months: list<array{key: string, label: string}>, by_month: array<string, list<int>>}
     */
    private function countsBySource(): array
    {
        $all = CrmCandidate::query()
            ->select('source', DB::raw('COUNT(*) as total'))
            ->where('abandon', false)
            ->groupBy('source')
            ->pluck('total', 'source');

        $labels = [];
        foreach (CrmCandidate::SOURCES as $key => $label) {
            $labels[] = ['key' => $key, 'label' => $label];
        }

        $extras = $all->keys()->filter(fn ($key) => $key !== null && $key !== '' && ! isset(CrmCandidate::SOURCES[$key]));
        foreach ($extras as $key) {
            $labels[] = ['key' => (string) $key, 'label' => (string) $key];
        }
        $labels[] = ['key' => '__empty', 'label' => 'Non renseignée'];

        $normalized = collect();
        foreach ($all as $key => $total) {
            $source = ($key === null || $key === '') ? '__empty' : (string) $key;
            $normalized[$source] = (int) ($normalized[$source] ?? 0) + (int) $total;
        }
        $all = $normalized;

        $driver = DB::getDriverName();
        $ymExpr = $driver === 'sqlite'
            ? "strftime('%Y-%m', created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m')";

        $from = now()->subMonths(11)->startOfMonth();
        $rows = CrmCandidate::query()
            ->select(DB::raw("{$ymExpr} as ym"), 'source', DB::raw('COUNT(*) as total'))
            ->where('abandon', false)
            ->where('created_at', '>=', $from)
            ->groupBy(DB::raw($ymExpr), 'source')
            ->get();

        $months = collect(range(0, 11))->map(fn ($i) => now()->subMonths($i)->startOfMonth());
        $byMonth = ['all' => $this->sourceChart($all, $labels)['series']];
        $monthOptions = [];

        foreach ($months as $month) {
            $key = $month->format('Y-m');
            $counts = $rows->where('ym', $key)->mapWithKeys(function ($row) {
                $source = $row->source === null || $row->source === '' ? '__empty' : (string) $row->source;

                return [$source => (int) $row->total];
            });
            $byMonth[$key] = $this->sourceChart($counts, $labels)['series'];
            $monthOptions[] = [
                'key' => $key,
                'label' => ucfirst($month->locale('fr')->isoFormat('MMMM YYYY')),
            ];
        }

        return [
            'labels' => $labels,
            'all' => $all,
            'months' => $monthOptions,
            'by_month' => $byMonth,
        ];
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
