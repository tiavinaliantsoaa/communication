@php
    $title = $link->nom;
    $subtitle = 'Statistiques du lien de suivi';
@endphp

@extends('layouts.app')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div class="min-w-0">
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ $link->short_url }}" target="_blank" rel="noopener" class="font-mono text-sm text-escm-primary hover:underline truncate">{{ $link->short_url }}</a>
            <button type="button"
                onclick="navigator.clipboard.writeText(@js($link->short_url))"
                class="shrink-0 inline-flex items-center gap-1 text-xs text-slate-500 hover:text-escm-primary px-2 py-1 rounded-md border border-slate-200 bg-white">
                Copier
            </button>
            @unless($link->actif)
                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">Inactif</span>
            @endunless
        </div>
        <p class="mt-1 text-sm text-slate-500 truncate" title="{{ $link->destination_url }}">→ {{ $link->destination_url }}</p>
    </div>
    <div class="flex items-center gap-2 shrink-0">
        <a href="{{ route('suivi-liens.index') }}" class="text-sm text-slate-600 hover:text-slate-900 px-3 py-2">Retour</a>
        @if(auth()->user()->canAccess('suivi_liens.update'))
        <a href="{{ route('suivi-liens.edit', $link) }}" class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-medium px-4 py-2 rounded-lg">Modifier</a>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <x-kpi-card label="Total des clics" :value="number_format($clicksTotal, 0, ',', ' ')" icon-color="blue">
        <x-slot:icon>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>
        </x-slot:icon>
    </x-kpi-card>
    <x-kpi-card label="Clics aujourd’hui" :value="number_format($clicksToday, 0, ',', ' ')" icon-color="green">
        <x-slot:icon>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </x-slot:icon>
    </x-kpi-card>
    <x-kpi-card label="Clics cette semaine" :value="number_format($clicksWeek, 0, ',', ' ')" icon-color="orange">
        <x-slot:icon>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </x-slot:icon>
    </x-kpi-card>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
    <div class="xl:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <h3 class="text-sm font-semibold text-slate-800 mb-4">Évolution des clics (30 jours)</h3>
        <div id="chart-clicks" class="h-64"></div>
    </div>
    <div class="space-y-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <h3 class="text-sm font-semibold text-slate-800 mb-4">Par appareil</h3>
            <div id="chart-device" class="h-40"></div>
            @if($byDevice->isEmpty())
                <p class="text-xs text-slate-500 text-center">Aucune donnée</p>
            @endif
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <h3 class="text-sm font-semibold text-slate-800 mb-4">Par navigateur</h3>
            <div id="chart-browser" class="h-40"></div>
            @if($byBrowser->isEmpty())
                <p class="text-xs text-slate-500 text-center">Aucune donnée</p>
            @endif
        </div>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100">
        <h3 class="text-sm font-semibold text-slate-800">Dernières visites</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100 bg-slate-50/50">
                    <th class="px-5 py-3">Date</th>
                    <th class="px-3 py-3">IP</th>
                    <th class="px-3 py-3 hidden md:table-cell">Localisation</th>
                    <th class="px-3 py-3">Appareil</th>
                    <th class="px-3 py-3 hidden sm:table-cell">OS</th>
                    <th class="px-3 py-3 hidden sm:table-cell">Navigateur</th>
                    <th class="px-5 py-3 hidden lg:table-cell">Referer</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($visits as $visit)
                <tr class="hover:bg-slate-50/50">
                    <td class="px-5 py-3 text-slate-700 whitespace-nowrap">{{ $visit->created_at?->format('d/m/Y H:i') }}</td>
                    <td class="px-3 py-3 font-mono text-xs text-slate-600">{{ $visit->ip ?? '—' }}</td>
                    <td class="px-3 py-3 text-slate-600 hidden md:table-cell">
                        @if($visit->city || $visit->country)
                            {{ collect([$visit->city, $visit->country])->filter()->implode(', ') }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-3 py-3 text-slate-700">{{ $visit->device ?? '—' }}</td>
                    <td class="px-3 py-3 text-slate-600 hidden sm:table-cell">{{ $visit->os ?? '—' }}</td>
                    <td class="px-3 py-3 text-slate-600 hidden sm:table-cell">{{ $visit->browser ?? '—' }}</td>
                    <td class="px-5 py-3 text-slate-500 hidden lg:table-cell">
                        <span class="block truncate max-w-[200px]" title="{{ $visit->referer }}">{{ $visit->referer ?: '—' }}</span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-5 py-8 text-center text-slate-500">Aucune visite enregistrée.</td></tr>
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

    const labels = @json($chartLabels);
    const series = @json($chartSeries);
    const devices = @json($byDevice);
    const browsers = @json($byBrowser);

    new ApexCharts(document.querySelector('#chart-clicks'), {
        chart: { type: 'area', height: 256, toolbar: { show: false }, fontFamily: 'inherit' },
        series: [{ name: 'Clics', data: series }],
        xaxis: { categories: labels, labels: { style: { fontSize: '11px' } } },
        yaxis: { labels: { style: { fontSize: '11px' } }, min: 0, forceNiceScale: true },
        stroke: { curve: 'smooth', width: 2 },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05 } },
        colors: ['#1e40af'],
        dataLabels: { enabled: false },
        grid: { borderColor: '#e2e8f0', strokeDashArray: 4 },
        tooltip: { y: { formatter: v => v + ' clic' + (v > 1 ? 's' : '') } },
    }).render();

    const deviceLabels = Object.keys(devices);
    const deviceValues = Object.values(devices);
    if (deviceLabels.length) {
        new ApexCharts(document.querySelector('#chart-device'), {
            chart: { type: 'donut', height: 160, fontFamily: 'inherit' },
            series: deviceValues,
            labels: deviceLabels,
            colors: ['#1e40af', '#059669', '#ea580c'],
            legend: { position: 'bottom', fontSize: '11px' },
            dataLabels: { enabled: false },
            plotOptions: { pie: { donut: { size: '65%' } } },
        }).render();
    }

    const browserLabels = Object.keys(browsers);
    const browserValues = Object.values(browsers);
    if (browserLabels.length) {
        new ApexCharts(document.querySelector('#chart-browser'), {
            chart: { type: 'donut', height: 160, fontFamily: 'inherit' },
            series: browserValues,
            labels: browserLabels,
            colors: ['#2563eb', '#dc2626', '#ca8a04', '#7c3aed', '#0891b2', '#64748b'],
            legend: { position: 'bottom', fontSize: '11px' },
            dataLabels: { enabled: false },
            plotOptions: { pie: { donut: { size: '65%' } } },
        }).render();
    }
});
</script>
@endpush
