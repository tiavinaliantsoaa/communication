@php
    $title = 'Suivi de lien';
    $subtitle = 'Liens courts traçables';
@endphp

@extends('layouts.app')

@section('content')
@if(auth()->user()->canAccess('suivi_liens.create'))
<x-page-actions :create-route="route('suivi-liens.create')" create-label="Nouveau lien" />
@endif

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100 bg-slate-50/50">
                    <th class="px-5 py-3">Nom</th>
                    <th class="px-3 py-3">Lien court</th>
                    <th class="px-3 py-3 hidden lg:table-cell">Destination</th>
                    <th class="px-3 py-3 text-right">Clics</th>
                    <th class="px-3 py-3 hidden md:table-cell">Créé le</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($links as $link)
                <tr class="hover:bg-slate-50/50">
                    <td class="px-5 py-3">
                        <a href="{{ route('suivi-liens.show', $link) }}" class="font-medium text-slate-900 hover:text-escm-primary">{{ $link->nom }}</a>
                        @unless($link->actif)
                            <span class="ml-2 inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-600">Inactif</span>
                        @endunless
                    </td>
                    <td class="px-3 py-3">
                        <div class="flex items-center gap-2 max-w-xs">
                            <a href="{{ $link->short_url }}" target="_blank" rel="noopener" class="text-escm-primary hover:underline truncate font-mono text-xs">{{ $link->short_url }}</a>
                            <button type="button"
                                onclick="navigator.clipboard.writeText(@js($link->short_url))"
                                class="shrink-0 p-1 text-slate-400 hover:text-escm-primary rounded"
                                title="Copier">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            </button>
                        </div>
                    </td>
                    <td class="px-3 py-3 text-slate-600 hidden lg:table-cell">
                        <span class="block truncate max-w-[220px]" title="{{ $link->destination_url }}">{{ $link->destination_url }}</span>
                    </td>
                    <td class="px-3 py-3 text-right font-semibold text-slate-900">{{ number_format($link->clicks_count, 0, ',', ' ') }}</td>
                    <td class="px-3 py-3 text-slate-600 whitespace-nowrap hidden md:table-cell">{{ format_date($link->created_at) }}</td>
                    <td class="px-5 py-3">
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('suivi-liens.show', $link) }}" class="p-1.5 text-slate-400 hover:text-escm-primary rounded" title="Statistiques">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            </a>
                            @if(auth()->user()->canAccess('suivi_liens.update'))
                            <a href="{{ route('suivi-liens.edit', $link) }}" class="p-1.5 text-slate-400 hover:text-escm-primary rounded" title="Modifier">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            @endif
                            @if(auth()->user()->canAccess('suivi_liens.delete'))
                            <form action="{{ route('suivi-liens.destroy', $link) }}" method="POST" onsubmit="return confirm('Supprimer ce lien et toutes ses statistiques ?')">
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
                <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">Aucun lien de suivi pour le moment.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($links->hasPages())
    <div class="px-5 py-3 border-t border-slate-100">{{ $links->links() }}</div>
    @endif
</div>
@endsection
