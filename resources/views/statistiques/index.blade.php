@php
    $title = $title ?? 'Statistiques';
    $subtitle = $subtitle ?? '';
@endphp

@extends('layouts.app')

@section('content')
@if($kpis['api_connected'])
<div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 flex items-start gap-3">
    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <div>
        <p class="font-semibold">Meta Graph API connectée</p>
        <p class="mt-0.5 text-emerald-700">Données Facebook & Instagram synchronisées (cache {{ (int) config('services.meta.cache_ttl', 1800) / 60 }} min).</p>
        @if(!empty($apiError))
            <p class="mt-2 text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">{{ $apiError }}</p>
        @endif
    </div>
</div>
@else
<div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 flex items-start gap-3">
    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <div>
        <p class="font-semibold">APIs Facebook & Instagram non connectées</p>
        <p class="mt-0.5 text-amber-700">Les données ci-dessous sont des exemples. Renseignez <code class="text-xs bg-amber-100 px-1 rounded">META_PAGE_ID</code> et <code class="text-xs bg-amber-100 px-1 rounded">META_PAGE_ACCESS_TOKEN</code> dans <code class="text-xs bg-amber-100 px-1 rounded">.env</code> (optionnel : <code class="text-xs bg-amber-100 px-1 rounded">META_IG_USER_ID</code>).</p>
        @if(!empty($apiError))
            <p class="mt-2 text-amber-900 font-medium">{{ $apiError }}</p>
        @endif
    </div>
</div>
@endif

{{-- KPIs --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <x-kpi-card label="Followers Facebook" :value="number_format($kpis['followers_fb'], 0, ',', ' ')" subtext="Page ESCM" icon-color="blue">
        <x-slot:icon>
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12a10 10 0 10-11.5 9.9v-7H8v-3h2.5V9.5c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.4h-1.2c-1.2 0-1.6.8-1.6 1.5V12H16l-.4 3h-2.6v7A10 10 0 0022 12z"/></svg>
        </x-slot:icon>
    </x-kpi-card>

    <x-kpi-card label="Followers Instagram" :value="number_format($kpis['followers_ig'], 0, ',', ' ')" subtext="Compte ESCM" icon-color="purple">
        <x-slot:icon>
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M7 2h10a5 5 0 015 5v10a5 5 0 01-5 5H7a5 5 0 01-5-5V7a5 5 0 015-5zm10 2H7a3 3 0 00-3 3v10a3 3 0 003 3h10a3 3 0 003-3V7a3 3 0 00-3-3zm-5 3.5A4.5 4.5 0 1112 16.5 4.5 4.5 0 0112 7.5zm0 2A2.5 2.5 0 1014.5 12 2.5 2.5 0 0012 9.5zM17.5 6.8a1 1 0 11-1 1 1 1 0 011-1z"/></svg>
        </x-slot:icon>
    </x-kpi-card>

    <x-kpi-card label="Engagement moyen" :value="$kpis['engagement_moyen'] . '%'" subtext="Sur le mois" icon-color="green" :progress="$kpis['engagement_moyen'] * 10" progress-label="Taux d'engagement" progress-color="green">
        <x-slot:icon>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
        </x-slot:icon>
    </x-kpi-card>

    <x-kpi-card label="Publications" :value="(string) $kpis['posts_mois']" subtext="Ce mois" icon-color="orange">
        <x-slot:icon>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
        </x-slot:icon>
    </x-kpi-card>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
    {{-- Engagement chart --}}
    <div class="xl:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <h3 class="text-sm font-semibold text-slate-900 mb-4">Engagement mensuel (%)</h3>
        <div id="chart-engagement" class="h-72"></div>
    </div>

    {{-- Top posts summary --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <h3 class="text-sm font-semibold text-slate-900 mb-4">Résumé réseaux</h3>
        <div class="space-y-4">
            <div class="rounded-lg bg-blue-50 border border-blue-100 p-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-blue-600 mb-1">Facebook</p>
                <p class="text-2xl font-bold text-slate-900">{{ end($engagementMensuel['facebook']) }}%</p>
                <p class="text-xs text-slate-500 mt-1">Engagement du mois en cours</p>
            </div>
            <div class="rounded-lg bg-purple-50 border border-purple-100 p-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-purple-600 mb-1">Instagram</p>
                <p class="text-2xl font-bold text-slate-900">{{ end($engagementMensuel['instagram']) }}%</p>
                <p class="text-xs text-slate-500 mt-1">Engagement du mois en cours</p>
            </div>
        </div>
    </div>
</div>

{{-- Top posts --}}
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100">
        <h3 class="text-sm font-semibold text-slate-900">Posts les plus aimés</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100 bg-slate-50/50">
                    <th class="px-5 py-3">Réseau</th>
                    <th class="px-3 py-3">Publication</th>
                    <th class="px-3 py-3 text-right">Likes</th>
                    <th class="px-3 py-3 text-right hidden sm:table-cell">Commentaires</th>
                    <th class="px-3 py-3 text-right hidden md:table-cell">Partages</th>
                    <th class="px-3 py-3 text-right">Engagement</th>
                    <th class="px-5 py-3">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($topPosts as $post)
                <tr class="hover:bg-slate-50/50">
                    <td class="px-5 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $post['reseau'] === 'Instagram' ? 'bg-pink-100 text-pink-700' : 'bg-blue-100 text-blue-700' }}">
                            {{ $post['reseau'] }}
                        </span>
                    </td>
                    <td class="px-3 py-3 font-medium text-slate-900">{{ $post['titre'] }}</td>
                    <td class="px-3 py-3 text-right font-medium text-slate-900">{{ number_format($post['likes'], 0, ',', ' ') }}</td>
                    <td class="px-3 py-3 text-right text-slate-600 hidden sm:table-cell">{{ $post['commentaires'] }}</td>
                    <td class="px-3 py-3 text-right text-slate-600 hidden md:table-cell">{{ $post['partages'] }}</td>
                    <td class="px-3 py-3 text-right font-semibold text-green-700">{{ $post['engagement'] }}%</td>
                    <td class="px-5 py-3 text-slate-500 whitespace-nowrap">{{ \Carbon\Carbon::parse($post['date'])->format('d/m/Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const el = document.querySelector('#chart-engagement');
    if (!el || typeof ApexCharts === 'undefined') return;

    const chart = new ApexCharts(el, {
        chart: { type: 'area', height: 280, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
        series: [
            { name: 'Facebook', data: @json($engagementMensuel['facebook']) },
            { name: 'Instagram', data: @json($engagementMensuel['instagram']) },
        ],
        colors: ['#2563eb', '#a855f7'],
        stroke: { curve: 'smooth', width: 2 },
        fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.05 } },
        dataLabels: { enabled: false },
        xaxis: { categories: @json($engagementMensuel['labels']) },
        yaxis: { labels: { formatter: (v) => v.toFixed(1) + '%' } },
        legend: { position: 'top', horizontalAlign: 'right' },
        grid: { borderColor: '#e2e8f0', strokeDashArray: 4 },
        tooltip: { y: { formatter: (v) => v.toFixed(1) + '%' } },
    });
    chart.render();
});
</script>
@endpush
