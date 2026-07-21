@php
    $title = 'Dashboard';
    $subtitle = 'Vue d\'ensemble du service communication';
@endphp

@extends('layouts.app')

@section('content')
{{-- KPI Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4 mb-6">
    <x-kpi-card label="Budget annuel" :value="format_ar($kpis['budget_annuel'])" :subtext="'Restant : ' . format_ar($kpis['budget_annuel_restant'])" icon-color="blue" :progress="$kpis['pct_annuel_alloue']" :progress-label="$kpis['pct_annuel_alloue'] . '% alloué en mensuel'" progress-color="blue">
        <x-slot:icon>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
        </x-slot:icon>
    </x-kpi-card>

    <x-kpi-card
        label="Budget mensuel"
        :value="format_ar($kpis['budget_mensuel'])"
        :subtext="$kpis['report_precedent'] > 0 ? 'Effectif : ' . format_ar($kpis['budget_effectif']) . ' (report −' . format_ar($kpis['report_precedent']) . ')' : 'Budget alloué'"
        icon-color="blue"
        :progress="min(100, $kpis['pct_utilise'])"
        :progress-label="$kpis['pct_utilise'] . '% utilisé'"
        :progress-color="$kpis['is_depassement'] ? 'red' : 'blue'"
    >
        <x-slot:icon>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
        </x-slot:icon>
    </x-kpi-card>

    <x-kpi-card
        label="Dépensé"
        :value="format_ar($kpis['depense'])"
        :subtext="$kpis['nb_operations'] . ' opérations'"
        :icon-color="$kpis['is_depassement'] ? 'red' : 'green'"
        :progress="min(100, $kpis['pct_utilise'])"
        :progress-label="$kpis['pct_utilise'] . '% du budget'"
        :progress-color="$kpis['is_depassement'] ? 'red' : 'green'"
        :value-class="$kpis['is_depassement'] ? 'text-red-600' : 'text-slate-900'"
    >
        <x-slot:icon>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
        </x-slot:icon>
    </x-kpi-card>

    <x-kpi-card
        label="Reste disponible"
        :value="format_ar($kpis['reste'])"
        :subtext="$kpis['is_depassement'] ? 'Dépassé — reporté sur le mois suivant' : 'Disponible ce mois'"
        :icon-color="$kpis['is_depassement'] ? 'red' : 'purple'"
        :progress="$kpis['is_depassement'] ? 100 : max(0, $kpis['pct_restant'])"
        :progress-label="$kpis['is_depassement'] ? 'Dépassement ' . format_ar($kpis['depassement']) : $kpis['pct_restant'] . '% restant'"
        :progress-color="$kpis['is_depassement'] ? 'red' : 'purple'"
        :value-class="$kpis['is_depassement'] ? 'text-red-600' : 'text-slate-900'"
    >
        <x-slot:icon>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </x-slot:icon>
    </x-kpi-card>

    <x-kpi-card label="Boost Facebook" :value="format_ar($kpis['sponsoring'])" :subtext="$kpis['nb_campagnes'] . ' boosts actifs'" icon-color="orange" :progress="$kpis['pct_sponsoring']" :progress-label="$kpis['pct_sponsoring'] . '% du budget'" progress-color="orange">
        <x-slot:icon>
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12a10 10 0 10-11.5 9.9v-7H8v-3h2.5V9.5c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.4h-1.2c-1.2 0-1.6.8-1.6 1.5V12H16l-.4 3h-2.6v7A10 10 0 0022 12z"/></svg>
        </x-slot:icon>
    </x-kpi-card>
</div>

{{-- Charts --}}
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
    <div class="xl:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <h3 class="text-sm font-semibold text-slate-900 mb-4">Évolution Budget vs Dépenses {{ $annee }}</h3>
        <div id="chart-evolution" class="h-72"></div>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <h3 class="text-sm font-semibold text-slate-900 mb-4">Répartition des dépenses — {{ ucfirst($moisLabel) }}</h3>
        <div id="chart-repartition" class="h-72"></div>
    </div>
</div>

{{-- Bottom widgets --}}
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    {{-- Dépenses récentes --}}
    <div class="xl:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="text-sm font-semibold text-slate-900">Dépenses — {{ ucfirst($moisLabel) }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100">
                        <th class="px-5 py-3">Date</th>
                        <th class="px-3 py-3">Fournisseur</th>
                        <th class="px-3 py-3 hidden md:table-cell">Objet</th>
                        <th class="px-3 py-3 hidden lg:table-cell">Campagne</th>
                        <th class="px-3 py-3 text-right">Montant</th>
                        <th class="px-5 py-3">Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($depensesRecentes as $depense)
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-5 py-3 text-slate-600 whitespace-nowrap">{{ $depense->date_depense->format('d/m/Y') }}</td>
                        <td class="px-3 py-3 font-medium text-slate-900">{{ $depense->fournisseur }}</td>
                        <td class="px-3 py-3 text-slate-600 hidden md:table-cell">{{ $depense->objet }}</td>
                        <td class="px-3 py-3 text-slate-600 hidden lg:table-cell">{{ $depense->campagne }}</td>
                        <td class="px-3 py-3 text-right font-medium text-slate-900 whitespace-nowrap">{{ format_ar($depense->montant) }}</td>
                        <td class="px-5 py-3"><x-status-badge :statut="$depense->statut_affiche" /></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-8 text-center text-slate-500">Aucune dépense pour {{ ucfirst($moisLabel) }}.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 border-t border-slate-100 text-center">
            <a href="{{ route('depenses.index') }}" class="text-sm font-medium text-escm-primary hover:text-escm-primary-dark">Voir toutes les dépenses</a>
        </div>
    </div>

    {{-- Alertes --}}
    <div id="alertes" class="bg-white rounded-xl border border-slate-200 shadow-sm">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-900">Alertes</h3>
            @if($nbAlertes > 0)
                <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full bg-red-500 text-[10px] font-bold text-white">{{ $nbAlertes }}</span>
            @endif
        </div>
        <div class="divide-y divide-slate-100 max-h-[28rem] overflow-y-auto">
            @forelse($alertes as $alerte)
            <a href="{{ $alerte['url'] ?? '#' }}" class="px-5 py-4 flex gap-3 hover:bg-slate-50/80 transition-colors block">
                <div class="flex-shrink-0 mt-0.5">
                    @if($alerte['type'] === 'danger')
                        <div class="w-8 h-8 rounded-full bg-red-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        </div>
                    @elseif($alerte['type'] === 'warning')
                        <div class="w-8 h-8 rounded-full bg-orange-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-orange-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        </div>
                    @else
                        <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                        </div>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-sm font-semibold text-slate-900">{{ $alerte['titre'] }}</p>
                        <span class="text-[11px] text-slate-400 whitespace-nowrap">{{ $alerte['temps'] }}</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $alerte['description'] }}</p>
                </div>
            </a>
            @empty
            <div class="px-5 py-8 text-center">
                <div class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-green-50 mb-2">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <p class="text-sm font-medium text-slate-700">Aucune alerte</p>
                <p class="text-xs text-slate-500 mt-0.5">Tout est sous contrôle pour le moment.</p>
            </div>
            @endforelse
        </div>
    </div>
</div>

{{-- Stock Marketing --}}
<div class="mt-6 bg-white rounded-xl border border-slate-200 shadow-sm">
    <div class="px-5 py-4 border-b border-slate-100">
        <h3 class="text-sm font-semibold text-slate-900">Stock Marketing</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100">
                    <th class="px-5 py-3">Article</th>
                    <th class="px-5 py-3 text-center">Stock</th>
                    <th class="px-5 py-3">Statut</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($stocks as $stock)
                <tr class="hover:bg-slate-50/50">
                    <td class="px-5 py-3 font-medium text-slate-900">{{ $stock->article }}</td>
                    <td class="px-5 py-3 text-center text-slate-700">{{ $stock->quantite }}</td>
                    <td class="px-5 py-3"><x-status-badge :statut="$stock->statut" /></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-5 py-3 border-t border-slate-100 text-center">
        <a href="{{ route('stocks.index') }}" class="text-sm font-medium text-escm-primary hover:text-escm-primary-dark">Voir tout le stock</a>
    </div>
</div>
@endsection

@php
    $title = 'Dashboard';
    $subtitle = 'Vue d\'ensemble du service communication';
@endphp

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const evolutionData = @json($chartEvolution);
    const repartitionData = @json($chartRepartition);

    if (typeof ApexCharts !== 'undefined') {
        const formatShort = (v) => {
            const n = Number(v) || 0;
            const abs = Math.abs(n);
            const sign = n < 0 ? '-' : '';
            let value, suffix, decimals;

            if (abs >= 1e12) {
                value = abs / 1e12;
                suffix = 'T';
            } else if (abs >= 1e6) {
                value = abs / 1e6;
                suffix = 'M';
            } else if (abs >= 1e3) {
                value = abs / 1e3;
                suffix = 'K';
            } else {
                return sign + Math.round(abs).toString();
            }

            decimals = value >= 100 ? 0 : (value >= 10 ? 1 : 2);
            let compact = value.toFixed(decimals);
            if (compact.includes('.')) {
                compact = compact.replace(/\.?0+$/, '');
            }

            return sign + compact + suffix;
        };

        new ApexCharts(document.querySelector('#chart-evolution'), {
            chart: { type: 'line', height: 288, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
            series: [
                { name: 'Budget prévu', data: evolutionData.budget_prevu },
                { name: 'Dépenses', data: evolutionData.depenses },
                { name: 'Reste', data: evolutionData.reste },
            ],
            colors: ['#3b82f6', '#f97316', '#22c55e'],
            stroke: { width: [2, 2, 2], dashArray: [5, 0, 0], curve: 'smooth' },
            xaxis: { categories: evolutionData.labels, labels: { style: { colors: '#94a3b8', fontSize: '11px' } } },
            yaxis: {
                labels: {
                    formatter: (v) => formatShort(v),
                    style: { colors: '#94a3b8', fontSize: '11px' }
                }
            },
            grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
            legend: { position: 'top', horizontalAlign: 'right', fontSize: '12px', markers: { radius: 12 } },
            tooltip: { y: { formatter: (v) => new Intl.NumberFormat('fr-FR').format(v) + ' Ar' } },
        }).render();

        new ApexCharts(document.querySelector('#chart-repartition'), {
            chart: { type: 'donut', height: 288, fontFamily: 'Inter, sans-serif' },
            series: repartitionData.items.map(i => i.montant),
            labels: repartitionData.items.map(i => i.label),
            colors: repartitionData.items.map(i => i.color),
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total',
                                formatter: () => formatShort(repartitionData.total) + ' Ar'
                            }
                        }
                    }
                }
            },
            legend: { position: 'bottom', fontSize: '11px' },
            dataLabels: { enabled: false },
            tooltip: { y: { formatter: (v) => new Intl.NumberFormat('fr-FR').format(v) + ' Ar' } },
        }).render();
    }
});
</script>
@endpush
