@php
    $title = 'Liens candidats';
    $subtitle = 'Liens uniques pour passer les tests en ligne';
@endphp

@extends('layouts.app')

@section('content')
@if(session('lien_cree'))
<div class="mb-4 rounded-xl border border-escm-primary/20 bg-blue-50 px-4 py-3 text-sm text-slate-800" x-data>
    <p class="font-medium text-escm-primary mb-1">Lien unique créé</p>
    <div class="flex flex-col sm:flex-row gap-2 items-stretch sm:items-center">
        <input type="text" readonly value="{{ session('lien_cree') }}" id="lien-copie"
               class="flex-1 rounded-lg border-slate-300 text-sm bg-white">
        <button type="button"
                onclick="navigator.clipboard.writeText(document.getElementById('lien-copie').value)"
                class="rounded-lg bg-escm-primary px-4 py-2 text-sm font-medium text-white hover:bg-escm-primary-dark">
            Copier
        </button>
    </div>
    <p class="mt-1 text-xs text-slate-500">Usage unique — le candidat ne peut l’ouvrir qu’une fois pour démarrer.</p>
</div>
@endif

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <form method="GET" class="flex flex-wrap items-center gap-2">
        <select name="statut" onchange="this.form.submit()" class="rounded-lg border-slate-200 text-sm focus:border-escm-primary focus:ring-escm-primary">
            <option value="">Tous les statuts</option>
            @foreach($statuts as $key => $label)
                <option value="{{ $key }}" @selected($statutActif === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="test" onchange="this.form.submit()" class="rounded-lg border-slate-200 text-sm focus:border-escm-primary focus:ring-escm-primary">
            <option value="">Tous les tests</option>
            @foreach($tests as $t)
                <option value="{{ $t->id }}" @selected($testActif == $t->id)>{{ $t->nom }}</option>
            @endforeach
        </select>
    </form>
    @if(auth()->user()->canAccess('online_tests.create'))
        <a href="{{ route('crm.online-tests.links.create') }}" class="inline-flex items-center gap-1.5 bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-4 py-2.5 rounded-lg">
            + Générer un lien
        </a>
    @endif
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100 bg-slate-50/50">
                    <th class="px-5 py-3">Candidat</th>
                    <th class="px-3 py-3">Test</th>
                    <th class="px-3 py-3">Statut</th>
                    <th class="px-3 py-3">Créé le</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($invitations as $inv)
                <tr class="hover:bg-slate-50/50">
                    <td class="px-5 py-3">
                        <p class="font-medium text-slate-900">{{ $inv->candidat_nom }}</p>
                        @if($inv->candidat_email)<p class="text-xs text-slate-500">{{ $inv->candidat_email }}</p>@endif
                    </td>
                    <td class="px-3 py-3 text-slate-600">{{ $inv->test?->nom ?? '—' }}</td>
                    <td class="px-3 py-3">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $inv->statut_color }}">{{ $inv->statut_label }}</span>
                    </td>
                    <td class="px-3 py-3 text-slate-500 whitespace-nowrap">{{ $inv->created_at?->format('d/m/Y H:i') }}</td>
                    <td class="px-5 py-3 text-right space-x-2">
                        @if($inv->statut === 'en_attente')
                            <button type="button" onclick="navigator.clipboard.writeText(@js($inv->publicUrl()))" class="text-xs font-medium text-escm-primary hover:underline">Copier le lien</button>
                        @endif
                        @if(in_array($inv->statut, ['termine', 'annule', 'expire', 'en_cours'], true))
                            <a href="{{ route('crm.online-tests.results.show', $inv) }}" class="text-xs font-medium text-slate-600 hover:underline">Voir</a>
                        @endif
                        @if(auth()->user()->canAccess('online_tests.delete') && in_array($inv->statut, ['en_attente', 'annule'], true))
                            <form action="{{ route('crm.online-tests.links.destroy', $inv) }}" method="POST" class="inline" onsubmit="return confirm('Supprimer ce lien ?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-red-600 hover:underline">Suppr.</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">Aucun lien généré.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($invitations->hasPages())
    <div class="px-5 py-3 border-t border-slate-100">{{ $invitations->links() }}</div>
    @endif
</div>
@endsection
