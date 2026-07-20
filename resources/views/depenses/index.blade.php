@php
    $title = 'Dépenses';
    $subtitle = 'Liste des dépenses';
@endphp

@extends('layouts.app')

@section('content')
<x-page-actions :create-route="route('depenses.create')" create-label="Nouvelle dépense" />

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100 bg-slate-50/50">
                    <th class="px-5 py-3">Date</th>
                    <th class="px-3 py-3">Fournisseur</th>
                    <th class="px-3 py-3 hidden md:table-cell">Objet</th>
                    <th class="px-3 py-3 hidden lg:table-cell">Campagne</th>
                    <th class="px-3 py-3 text-right">Montant</th>
                    <th class="px-3 py-3">Statut</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($depenses as $depense)
                <tr class="hover:bg-slate-50/50">
                    <td class="px-5 py-3 text-slate-600 whitespace-nowrap">{{ $depense->date_depense->format('d/m/Y') }}</td>
                    <td class="px-3 py-3 font-medium text-slate-900">{{ $depense->fournisseur }}</td>
                    <td class="px-3 py-3 text-slate-600 hidden md:table-cell">{{ $depense->objet }}</td>
                    <td class="px-3 py-3 text-slate-600 hidden lg:table-cell">{{ $depense->campagne }}</td>
                    <td class="px-3 py-3 text-right font-medium whitespace-nowrap">{{ format_ar($depense->montant) }}</td>
                    <td class="px-3 py-3"><x-status-badge :statut="$depense->statut_affiche" /></td>
                    <td class="px-5 py-3">
                        <x-row-actions
                            :edit-route="route('depenses.edit', $depense)"
                            :delete-route="route('depenses.destroy', $depense)"
                            delete-confirm="Supprimer cette dépense ?"
                        />
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-5 py-8 text-center text-slate-500">Aucune dépense enregistrée.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($depenses->hasPages())
    <div class="px-5 py-3 border-t border-slate-100">{{ $depenses->links() }}</div>
    @endif
</div>
@endsection
