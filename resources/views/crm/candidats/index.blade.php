@php
    $title = 'CRM — Candidats';
    $subtitle = 'Gestion des prospects';
@endphp

@extends('layouts.app')

@section('content')
@if(auth()->user()->canAccess('crm.create'))
<x-page-actions :create-route="route('crm.candidats.create')" create-label="Nouveau candidat" />
@endif

<form method="GET" action="{{ route('crm.candidats.index') }}" class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 mb-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <div class="lg:col-span-2">
            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">Recherche</label>
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nom, téléphone, e-mail…"
                   class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
        </div>
        <div>
            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">Statut</label>
            <select name="statut" class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
                <option value="">Tous</option>
                @foreach($statuts as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['statut'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">Programme</label>
            <select name="programme" class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
                <option value="">Tous</option>
                @foreach($programmes as $programme)
                    <option value="{{ $programme }}" @selected(($filters['programme'] ?? '') === $programme)>{{ $programme }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">Conseiller</label>
            <select name="advisor_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
                <option value="">Tous</option>
                @foreach($advisors as $advisor)
                    <option value="{{ $advisor->id }}" @selected(($filters['advisor_id'] ?? '') == $advisor->id)>{{ $advisor->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="mt-3 flex flex-wrap items-center gap-3">
        <select name="sort" class="rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
            <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>Plus récents</option>
            <option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>Plus anciens</option>
        </select>
        <button type="submit" class="bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg">Filtrer</button>
        <a href="{{ route('crm.candidats.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Réinitialiser</a>
    </div>
</form>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100 bg-slate-50/50">
                    <th class="px-5 py-3">Nom</th>
                    <th class="px-3 py-3">Téléphone</th>
                    <th class="px-3 py-3 hidden lg:table-cell">E-mail</th>
                    <th class="px-3 py-3 hidden md:table-cell">Programme</th>
                    <th class="px-3 py-3 hidden xl:table-cell">Conseiller</th>
                    <th class="px-3 py-3">Statut</th>
                    <th class="px-3 py-3 hidden md:table-cell">Dernière interaction</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($candidates as $c)
                <tr class="hover:bg-slate-50/50">
                    <td class="px-5 py-3">
                        <a href="{{ route('crm.candidats.show', $c) }}" class="font-medium text-slate-900 hover:text-escm-primary">{{ $c->full_name }}</a>
                    </td>
                    <td class="px-3 py-3 text-slate-600 whitespace-nowrap">{{ $c->telephone ?: '—' }}</td>
                    <td class="px-3 py-3 text-slate-600 hidden lg:table-cell">{{ $c->email ?: '—' }}</td>
                    <td class="px-3 py-3 text-slate-600 hidden md:table-cell">{{ $c->programme ?: '—' }}</td>
                    <td class="px-3 py-3 text-slate-600 hidden xl:table-cell">{{ $c->advisor?->name ?: '—' }}</td>
                    <td class="px-3 py-3">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $c->statut_color }}">{{ $c->statut_label }}</span>
                    </td>
                    <td class="px-3 py-3 text-slate-600 whitespace-nowrap hidden md:table-cell">{{ $c->last_interaction_at ? $c->last_interaction_at->format('d/m/Y H:i') : '—' }}</td>
                    <td class="px-5 py-3">
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('crm.candidats.show', $c) }}" class="p-1.5 text-slate-400 hover:text-escm-primary rounded" title="Voir">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                            @if(auth()->user()->canAccess('crm.update'))
                            <a href="{{ route('crm.candidats.edit', $c) }}" class="p-1.5 text-slate-400 hover:text-escm-primary rounded" title="Modifier">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            @endif
                            @if(auth()->user()->canAccess('crm.delete'))
                            <form action="{{ route('crm.candidats.destroy', $c) }}" method="POST" onsubmit="return confirm('Supprimer ce candidat et tout son historique ?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-1.5 text-slate-400 hover:text-red-600 rounded" title="Supprimer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-5 py-10 text-center text-slate-500">Aucun candidat trouvé.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($candidates->hasPages())
    <div class="px-5 py-3 border-t border-slate-100">{{ $candidates->links() }}</div>
    @endif
</div>
@endsection
