@php
    $title = 'Campagnes';
    $subtitle = 'Boosts de publications Facebook';
@endphp

@extends('layouts.app')

@section('content')
<x-page-actions :create-route="route('campagnes.create')" create-label="Nouveau boost Facebook" />

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100 bg-slate-50/50">
                    <th class="px-5 py-3">Publication / Boost</th>
                    <th class="px-3 py-3 hidden md:table-cell">Objectif</th>
                    <th class="px-3 py-3 text-right">Budget</th>
                    <th class="px-3 py-3">Début</th>
                    <th class="px-3 py-3">Fin</th>
                    <th class="px-3 py-3">Statut</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($campagnes as $campagne)
                <tr class="hover:bg-slate-50/50">
                    <td class="px-5 py-3 font-medium text-slate-900">{{ $campagne->nom }}</td>
                    <td class="px-3 py-3 text-slate-600 hidden md:table-cell">{{ $campagne->objectif ?? '—' }}</td>
                    <td class="px-3 py-3 text-right font-medium whitespace-nowrap">{{ format_ar($campagne->budget) }}</td>
                    <td class="px-3 py-3 text-slate-600 whitespace-nowrap">{{ format_date($campagne->date_debut) }}</td>
                    <td class="px-3 py-3 text-slate-600 whitespace-nowrap">{{ format_date($campagne->date_fin) }}</td>
                    <td class="px-3 py-3">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                            {{ $campagne->statut === 'active' ? 'bg-blue-50 text-blue-700' : '' }}
                            {{ $campagne->statut === 'terminee' ? 'bg-green-50 text-green-700' : '' }}
                            {{ $campagne->statut === 'planifiee' ? 'bg-slate-100 text-slate-700' : '' }}
                        ">
                            {{ $campagne->statut_label }}
                        </span>
                    </td>
                    <td class="px-5 py-3">
                        <x-row-actions
                            :edit-route="route('campagnes.edit', $campagne)"
                            :delete-route="route('campagnes.destroy', $campagne)"
                            delete-confirm="Supprimer ce boost ? Le budget sera rétabli sur le budget mensuel."
                        />
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-5 py-8 text-center text-slate-500">Aucun boost Facebook enregistré.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($campagnes->hasPages())
    <div class="px-5 py-3 border-t border-slate-100">{{ $campagnes->links() }}</div>
    @endif
</div>
@endsection
