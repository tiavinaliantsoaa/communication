@php
    $title = 'Budget annuel '.$budgetAnnuel->annee;
    $subtitle = 'Détail de l’enveloppe, mois alloués et catégories';
@endphp

@extends('layouts.app')

@section('content')
<div class="mb-4">
    <a href="{{ route('budget-annuels.index') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Retour aux budgets annuels
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Budget annuel</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ format_ar($budgetAnnuel->montant) }}</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Alloué (mensuel)</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ format_ar($alloue) }}</p>
        <div class="mt-3 w-full bg-slate-100 rounded-full h-1.5">
            <div class="h-1.5 rounded-full {{ $pct >= 100 ? 'bg-red-500' : 'bg-escm-primary' }}" style="width: {{ min(100, $pct) }}%"></div>
        </div>
        <p class="mt-1 text-[11px] text-slate-500">{{ $pct }}% alloué</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Restant</p>
        <p class="mt-2 text-2xl font-semibold {{ $restant <= 0 ? 'text-red-600' : 'text-green-700' }}">{{ format_ar($restant) }}</p>
        @if(auth()->user()?->canAccess('budget_annuel.update'))
            <a href="{{ route('budget-annuels.edit', $budgetAnnuel) }}" class="mt-3 inline-flex text-sm font-medium text-escm-primary hover:underline">Modifier l’enveloppe</a>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
                <h3 class="text-sm font-semibold text-slate-800">Budgets mensuels {{ $budgetAnnuel->annee }}</h3>
                @if(auth()->user()?->canAccess('budget_mensuel.create'))
                    <a href="{{ route('budgets.create') }}" class="text-xs font-semibold text-escm-primary hover:underline">+ Nouveau mois</a>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100 bg-slate-50/50">
                            <th class="px-5 py-3">Mois</th>
                            <th class="px-3 py-3 text-right">Montant</th>
                            <th class="px-3 py-3">Répartition</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($budgetsMensuels as $mois)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-5 py-3 font-medium text-slate-900 capitalize">
                                {{ \Carbon\Carbon::create($mois->annee, $mois->mois, 1)->locale('fr')->isoFormat('MMMM') }}
                            </td>
                            <td class="px-3 py-3 text-right font-medium text-slate-900">{{ format_ar($mois->montant) }}</td>
                            <td class="px-3 py-3">
                                @if($mois->lignes->isNotEmpty())
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($mois->lignes as $ligne)
                                            <span class="inline-flex items-center rounded-full bg-slate-100 text-slate-700 px-2 py-0.5 text-[11px]">
                                                {{ $ligne->categorie?->nom ?? '—' }} · {{ format_ar($ligne->montant) }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">Non détaillé</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if(auth()->user()?->canAccess('budget_mensuel.update'))
                                    <a href="{{ route('budgets.edit', $mois) }}" class="text-xs font-medium text-escm-primary hover:underline">Détailler</a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-5 py-8 text-center text-slate-500">Aucun budget mensuel pour cette année.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <h3 class="text-sm font-semibold text-slate-800 mb-1">Catégories de dépenses</h3>
            <p class="text-xs text-slate-500 mb-4">Créez ici les catégories utilisées pour les dépenses et la répartition des budgets mensuels.</p>

            @if(auth()->user()?->canAccess('budget_annuel.update') || auth()->user()?->canAccess('budget_annuel.create'))
            <form action="{{ route('budget-annuels.categories.store') }}" method="POST" class="space-y-3 mb-5">
                @csrf
                <input type="hidden" name="budget_annuel_id" value="{{ $budgetAnnuel->id }}">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Nouvelle catégorie</label>
                    <input type="text" name="categorie_nom" value="{{ old('categorie_nom') }}" required maxlength="100"
                           placeholder="Ex. Transport, Communication…"
                           class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
                    @error('categorie_nom')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="w-full bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-4 py-2.5 rounded-lg">
                    Créer la catégorie
                </button>
            </form>
            @endif

            <ul class="divide-y divide-slate-100 border border-slate-100 rounded-lg overflow-hidden">
                @forelse($categories as $categorie)
                <li class="flex items-center justify-between gap-3 px-3 py-2.5 bg-white">
                    <span class="text-sm font-medium text-slate-800">{{ $categorie->nom }}</span>
                    @if(auth()->user()?->canAccess('budget_annuel.update') || auth()->user()?->canAccess('budget_annuel.delete'))
                    <form action="{{ route('budget-annuels.categories.destroy', $categorie) }}" method="POST"
                          onsubmit="return confirm('Supprimer cette catégorie ?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-slate-400 hover:text-red-600">Suppr.</button>
                    </form>
                    @endif
                </li>
                @empty
                <li class="px-3 py-6 text-center text-sm text-slate-500">Aucune catégorie pour ce département.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
