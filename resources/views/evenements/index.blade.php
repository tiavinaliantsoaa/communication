@php
    $title = 'Événements';
    $subtitle = 'Événements — '.ucfirst(\App\Support\Periode::label());
@endphp

@extends('layouts.app')

@section('content')
<x-page-actions :create-route="route('evenements.create')" create-label="Nouvel événement" />

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100 bg-slate-50/50">
                    <th class="px-5 py-3">Date</th>
                    <th class="px-3 py-3">Nom</th>
                    <th class="px-3 py-3 hidden md:table-cell">Type</th>
                    <th class="px-3 py-3 hidden lg:table-cell">Lieu</th>
                    <th class="px-3 py-3 text-right">Coût</th>
                    <th class="px-3 py-3">Statut</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($evenements as $evenement)
                <tr class="hover:bg-slate-50/50">
                    <td class="px-5 py-3 text-slate-600 whitespace-nowrap">
                        {{ $evenement->date_debut->format('d/m/Y') }}
                        @if($evenement->date_fin && !$evenement->date_fin->isSameDay($evenement->date_debut))
                            <span class="text-slate-400">→ {{ $evenement->date_fin->format('d/m/Y') }}</span>
                        @endif
                    </td>
                    <td class="px-3 py-3 font-medium text-slate-900">{{ $evenement->nom }}</td>
                    <td class="px-3 py-3 text-slate-600 hidden md:table-cell">{{ $evenement->type_label }}</td>
                    <td class="px-3 py-3 text-slate-600 hidden lg:table-cell">{{ $evenement->lieu ?? '—' }}</td>
                    <td class="px-3 py-3 text-right font-medium whitespace-nowrap">{{ format_ar($evenement->cout) }}</td>
                    <td class="px-3 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold
                            {{ $evenement->statut === 'termine' ? 'bg-green-100 text-green-700' : '' }}
                            {{ $evenement->statut === 'en_cours' ? 'bg-blue-100 text-blue-700' : '' }}
                            {{ $evenement->statut === 'planifie' ? 'bg-slate-100 text-slate-700' : '' }}
                            {{ $evenement->statut === 'annule' ? 'bg-red-100 text-red-700' : '' }}
                        ">{{ $evenement->statut_label }}</span>
                    </td>
                    <td class="px-5 py-3">
                        <x-row-actions
                            :edit-route="route('evenements.edit', $evenement)"
                            :delete-route="route('evenements.destroy', $evenement)"
                            delete-confirm="Supprimer cet événement ? Le coût sera rétabli sur le budget mensuel."
                        />
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-5 py-8 text-center text-slate-500">Aucun événement enregistré.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($evenements->hasPages())
    <div class="px-5 py-3 border-t border-slate-100">{{ $evenements->links() }}</div>
    @endif
</div>
@endsection
