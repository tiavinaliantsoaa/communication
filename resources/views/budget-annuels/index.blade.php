@php
    $title = 'Budget annuel';
    $subtitle = 'Enveloppe annuelle et répartition mensuelle';
@endphp

@extends('layouts.app')

@section('content')
<x-page-actions :create-route="route('budget-annuels.create')" create-label="Nouveau budget annuel" />

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100 bg-slate-50/50">
                    <th class="px-5 py-3">Année</th>
                    <th class="px-3 py-3 text-right">Budget annuel</th>
                    <th class="px-3 py-3 text-right">Alloué (mensuel)</th>
                    <th class="px-3 py-3 text-right">Restant</th>
                    <th class="px-3 py-3">Répartition</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($budgets as $budget)
                <tr class="hover:bg-slate-50/50">
                    <td class="px-5 py-3 font-medium text-slate-900">
                        <a href="{{ route('budget-annuels.show', $budget) }}" class="hover:text-escm-primary">{{ $budget->annee }}</a>
                    </td>
                    <td class="px-3 py-3 text-right font-medium text-slate-900">{{ format_ar($budget->montant) }}</td>
                    <td class="px-3 py-3 text-right text-slate-700">{{ format_ar($budget->alloue) }}</td>
                    <td class="px-3 py-3 text-right font-medium {{ $budget->restant <= 0 ? 'text-red-600' : 'text-green-700' }}">{{ format_ar($budget->restant) }}</td>
                    <td class="px-3 py-3 min-w-[140px]">
                        <div class="w-full bg-slate-100 rounded-full h-1.5 mb-1">
                            <div class="h-1.5 rounded-full {{ $budget->pct >= 100 ? 'bg-red-500' : 'bg-escm-primary' }}" style="width: {{ min(100, $budget->pct) }}%"></div>
                        </div>
                        <p class="text-[11px] text-slate-500">{{ $budget->pct }}% alloué</p>
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('budget-annuels.show', $budget) }}" class="text-xs font-medium text-escm-primary hover:underline">Détail</a>
                            <x-row-actions
                                :edit-route="route('budget-annuels.edit', $budget)"
                                :delete-route="route('budget-annuels.destroy', $budget)"
                                delete-confirm="Supprimer ce budget annuel ?"
                            />
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">Aucun budget annuel enregistré.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($budgets->hasPages())
    <div class="px-5 py-3 border-t border-slate-100">{{ $budgets->links() }}</div>
    @endif
</div>
@endsection
