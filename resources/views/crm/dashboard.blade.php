@php
    $title = 'CRM — Tableau de bord';
    $subtitle = 'Suivi des candidats et conversion';
@endphp

@extends('layouts.app')

@section('content')
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4 mb-6">
    <x-kpi-card label="Total candidats" :value="number_format($kpis['total'], 0, ',', ' ')" icon-color="blue">
        <x-slot:icon>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </x-slot:icon>
    </x-kpi-card>
    <x-kpi-card label="Nouveaux ce mois" :value="number_format($kpis['nouveaux_mois'], 0, ',', ' ')" icon-color="orange">
        <x-slot:icon>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/></svg>
        </x-slot:icon>
    </x-kpi-card>
    <x-kpi-card label="Inscrits" :value="number_format($kpis['inscrits'], 0, ',', ' ')" icon-color="green">
        <x-slot:icon>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </x-slot:icon>
    </x-kpi-card>
    <x-kpi-card label="Taux de conversion" :value="$kpis['conversion'].' %'" icon-color="purple">
        <x-slot:icon>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
        </x-slot:icon>
    </x-kpi-card>
    <x-kpi-card label="Perdus" :value="number_format($kpis['perdus'], 0, ',', ' ')" icon-color="red">
        <x-slot:icon>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/></svg>
        </x-slot:icon>
    </x-kpi-card>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
    <div class="xl:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <h3 class="text-sm font-semibold text-slate-800 mb-4">Candidats créés par mois</h3>
        <div id="crm-chart-monthly" class="h-64"></div>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <h3 class="text-sm font-semibold text-slate-800 mb-4">Répartition par statut</h3>
        <div id="crm-chart-status" class="h-64"></div>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 mb-6">
    <h3 class="text-sm font-semibold text-slate-800 mb-4">Entonnoir de conversion</h3>
    <div id="crm-chart-funnel" class="h-72"></div>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-slate-800">Derniers candidats</h3>
        <a href="{{ route('crm.candidats.index') }}" class="text-xs font-medium text-escm-primary hover:underline">Voir tous</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100 bg-slate-50/50">
                    <th class="px-5 py-3">Nom</th>
                    <th class="px-3 py-3">Téléphone</th>
                    <th class="px-3 py-3 hidden md:table-cell">Programme</th>
                    <th class="px-3 py-3">Statut</th>
                    <th class="px-5 py-3">Créé le</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($recent as $c)
                <tr class="hover:bg-slate-50/50 cursor-pointer" onclick="window.location='{{ route('crm.candidats.show', $c) }}'">
                    <td class="px-5 py-3 font-medium text-slate-900">{{ $c->full_name }}</td>
                    <td class="px-3 py-3 text-slate-600">{{ $c->telephone ?: '—' }}</td>
                    <td class="px-3 py-3 text-slate-600 hidden md:table-cell">{{ $c->programme ?: '—' }}</td>
                    <td class="px-3 py-3">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $c->statut_color }}">{{ $c->statut_label }}</span>
                    </td>
                    <td class="px-5 py-3 text-slate-600 whitespace-nowrap">{{ format_date($c->created_at) }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">Aucun candidat pour le moment. <a href="{{ route('crm.candidats.create') }}" class="text-escm-primary hover:underline">Créer le premier</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof ApexCharts === 'undefined') return;

    new ApexCharts(document.querySelector('#crm-chart-monthly'), {
        chart: { type: 'area', height: 256, toolbar: { show: false }, fontFamily: 'inherit' },
        series: [{ name: 'Candidats', data: @json($chartMonthly['series']) }],
        xaxis: { categories: @json($chartMonthly['labels']), labels: { style: { fontSize: '11px' } } },
        yaxis: { min: 0, forceNiceScale: true, labels: { style: { fontSize: '11px' } } },
        stroke: { curve: 'smooth', width: 2 },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05 } },
        colors: ['#1e40af'],
        dataLabels: { enabled: false },
        grid: { borderColor: '#e2e8f0', strokeDashArray: 4 },
    }).render();

    new ApexCharts(document.querySelector('#crm-chart-status'), {
        chart: { type: 'donut', height: 256, fontFamily: 'inherit' },
        series: @json($chartStatus['series']),
        labels: @json($chartStatus['labels']),
        colors: ['#64748b','#0ea5e9','#2563eb','#6366f1','#8b5cf6','#10b981','#15803d','#ef4444'],
        legend: { position: 'bottom', fontSize: '11px' },
        dataLabels: { enabled: false },
        plotOptions: { pie: { donut: { size: '65%' } } },
    }).render();

    new ApexCharts(document.querySelector('#crm-chart-funnel'), {
        chart: { type: 'bar', height: 288, toolbar: { show: false }, fontFamily: 'inherit' },
        series: [{ name: 'Candidats', data: @json($chartFunnel['series']) }],
        plotOptions: { bar: { borderRadius: 6, columnWidth: '45%', distributed: true } },
        colors: ['#64748b','#0ea5e9','#2563eb','#6366f1','#8b5cf6','#10b981','#15803d'],
        xaxis: { categories: @json($chartFunnel['labels']), labels: { style: { fontSize: '11px' } } },
        yaxis: { min: 0, forceNiceScale: true, labels: { style: { fontSize: '11px' } } },
        legend: { show: false },
        dataLabels: { enabled: true },
        grid: { borderColor: '#e2e8f0', strokeDashArray: 4 },
    }).render();
});
</script>
@endpush
