@php
    $title = 'IT Ticketing';
    $subtitle = 'Requêtes et suivi informatique';
@endphp

@extends('layouts.app')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <form method="GET" class="flex items-center gap-2">
        <select name="statut" onchange="this.form.submit()" class="rounded-lg border-slate-200 text-sm focus:border-escm-primary focus:ring-escm-primary">
            <option value="">Tous les statuts</option>
            @foreach($statuts as $key => $label)
                <option value="{{ $key }}" @selected(($statutActif ?? '') === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </form>
    @if(auth()->user()?->canAccess('it_ticketing.create'))
    <a href="{{ route('it-tickets.create') }}" class="inline-flex items-center gap-1.5 bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-4 py-2.5 rounded-lg">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Nouvelle requête
    </a>
    @endif
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100 bg-slate-50/50">
                    <th class="px-5 py-3">#</th>
                    <th class="px-3 py-3">Titre</th>
                    @if($canManage || auth()->user()?->isAdmin())
                    <th class="px-3 py-3">Demandeur</th>
                    @endif
                    <th class="px-3 py-3">Impact</th>
                    <th class="px-3 py-3">Statut</th>
                    <th class="px-3 py-3">Créé le</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($tickets as $ticket)
                <tr class="hover:bg-slate-50/50">
                    <td class="px-5 py-3 text-slate-500 font-mono text-xs">#{{ $ticket->id }}</td>
                    <td class="px-3 py-3">
                        <a href="{{ route('it-tickets.show', $ticket) }}" class="font-medium text-slate-900 hover:text-escm-primary">{{ $ticket->titre }}</a>
                    </td>
                    @if($canManage || auth()->user()?->isAdmin())
                    <td class="px-3 py-3 text-slate-600">{{ $ticket->user?->name ?? '—' }}</td>
                    @endif
                    <td class="px-3 py-3">
                        @if($ticket->impact)
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $ticket->impact_color }}">
                            {{ $ticket->impact_label }}
                        </span>
                        @else
                        <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="px-3 py-3">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $ticket->statut_color }}">
                            {{ $ticket->statut_label }}
                        </span>
                    </td>
                    <td class="px-3 py-3 text-slate-500 whitespace-nowrap">{{ $ticket->created_at?->locale('fr')->isoFormat('D MMM YYYY, HH:mm') }}</td>
                    <td class="px-5 py-3 text-right">
                        <a href="{{ route('it-tickets.show', $ticket) }}" class="inline-flex items-center gap-1 text-xs font-medium text-escm-primary hover:underline">
                            Suivre
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-5 py-10 text-center text-slate-500">
                        Aucune requête pour le moment.
                        @if(auth()->user()?->canAccess('it_ticketing.create'))
                            <a href="{{ route('it-tickets.create') }}" class="text-escm-primary font-medium hover:underline">Créer une requête</a>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($tickets->hasPages())
    <div class="px-5 py-3 border-t border-slate-100">{{ $tickets->links() }}</div>
    @endif
</div>
@endsection
