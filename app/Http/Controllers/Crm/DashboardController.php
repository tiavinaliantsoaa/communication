<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmCandidate;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $total = CrmCandidate::count();
        $nouveauxMois = CrmCandidate::where('created_at', '>=', now()->startOfMonth())->count();
        $inscrits = CrmCandidate::where('statut', 'inscrit')->count();
        $perdus = CrmCandidate::where('statut', 'perdu')->count();
        $conversion = $total > 0 ? round(($inscrits / $total) * 100, 1) : 0;

        $kpis = [
            'total' => $total,
            'nouveaux_mois' => $nouveauxMois,
            'inscrits' => $inscrits,
            'conversion' => $conversion,
            'perdus' => $perdus,
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
            ->groupBy('statut')
            ->pluck('total', 'statut');

        $chartStatus = [
            'labels' => collect(CrmCandidate::STATUTS)->map(fn ($label, $key) => $label)->values()->all(),
            'series' => collect(CrmCandidate::STATUTS)->map(fn ($label, $key) => (int) ($byStatus[$key] ?? 0))->values()->all(),
        ];

        $funnelCounts = collect(CrmCandidate::FUNNEL_STATUTS)->mapWithKeys(function ($key) {
            return [$key => CrmCandidate::where('statut', $key)->count()];
        });

        $chartFunnel = [
            'labels' => collect(CrmCandidate::FUNNEL_STATUTS)->map(fn ($k) => CrmCandidate::STATUTS[$k])->values()->all(),
            'series' => $funnelCounts->values()->all(),
        ];

        $recent = CrmCandidate::with('advisor')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('crm.dashboard', compact('kpis', 'chartMonthly', 'chartStatus', 'chartFunnel', 'recent'));
    }
}
