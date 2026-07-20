@props(['statut'])

@php
    $classes = match($statut) {
        'paye' => 'bg-green-50 text-green-700 ring-green-600/20',
        'paye_partiellement' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        'en_attente' => 'bg-orange-50 text-orange-700 ring-orange-600/20',
        'valide' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
        'bon' => 'bg-green-50 text-green-700 ring-green-600/20',
        'moyen' => 'bg-orange-50 text-orange-700 ring-orange-600/20',
        'faible' => 'bg-red-50 text-red-700 ring-red-600/20',
        default => 'bg-slate-50 text-slate-700 ring-slate-600/20',
    };
    $labels = [
        'paye' => 'Payé',
        'paye_partiellement' => 'Payé partiellement',
        'en_attente' => 'En attente',
        'valide' => 'Approuvé',
        'bon' => 'Bon',
        'moyen' => 'Moyen',
        'faible' => 'Faible',
    ];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset $classes"]) }}>
    {{ $labels[$statut] ?? $statut }}
</span>
