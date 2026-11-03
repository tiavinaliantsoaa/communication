@php
    $title = 'Budget mensuel';
    $subtitle = 'Gestion des budgets mensuels';
@endphp

@extends('layouts.app')

@section('content')
<x-page-actions :create-route="route('budgets.create')" create-label="Nouveau budget" />

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100 bg-slate-50/50">
                    <th class="px-5 py-3">Année</th>
                    <th class="px-3 py-3">Mois</th>
                    <th class="px-3 py-3 text-right">Montant</th>
                    <th class="px-3 py-3">Répartition</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($budgets as $budget)
                <tr class="hover:bg-slate-50/50">
                    <td class="px-5 py-3 font-medium text-slate-900">{{ $budget->annee }}</td>
                    <td class="px-3 py-3 text-slate-600 capitalize">{{ \Carbon\Carbon::create($budget->annee, $budget->mois, 1)->locale('fr')->isoFormat('MMMM') }}</td>
                    <td class="px-3 py-3 text-right font-medium text-slate-900">{{ format_ar($budget->montant) }}</td>
                    <td class="px-3 py-3">
                        @if(($budget->lignes_count ?? 0) > 0)
                            <span class="inline-flex items-center rounded-full bg-escm-primary/10 text-escm-primary px-2 py-0.5 text-xs font-medium">
                                {{ $budget->lignes_count }} catégorie{{ $budget->lignes_count > 1 ? 's' : '' }}
                            </span>
                        @else
                            <span class="text-xs text-slate-400">Non détaillé</span>
                        @endif
                    </td>
                    <td class="px-5 py-3">
                        <x-row-actions
                            :edit-route="route('budgets.edit', $budget)"
                            :delete-route="route('budgets.destroy', $budget)"
                            delete-confirm="Supprimer ce budget ?"
                        />
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">Aucun budget enregistré.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($budgets->hasPages())
    <div class="px-5 py-3 border-t border-slate-100">{{ $budgets->links() }}</div>
    @endif
</div>
@endsection
