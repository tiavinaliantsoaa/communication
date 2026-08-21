@php
    $title = $candidate->full_name;
    $subtitle = 'Fiche candidat CRM';
@endphp

@extends('layouts.app')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
    <div>
        <div class="flex items-center gap-3 flex-wrap">
            <h2 class="text-xl font-semibold text-slate-900">{{ $candidate->full_name }}</h2>
            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $candidate->statut_color }}">{{ $candidate->statut_label }}</span>
        </div>
        <p class="mt-1 text-sm text-slate-500">
            {{ $candidate->programme ?: 'Programme non renseigné' }}
            @if($candidate->advisor)
                · Conseiller : {{ $candidate->advisor->name }}
            @endif
        </p>
    </div>
    <div class="flex items-center gap-2 shrink-0">
        <a href="{{ route('crm.candidats.index') }}" class="text-sm text-slate-600 hover:text-slate-900 px-3 py-2">Retour</a>
        @if(auth()->user()->canAccess('crm.update'))
        <a href="{{ route('crm.candidats.edit', $candidate) }}" class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-medium px-4 py-2 rounded-lg">Modifier</a>
        @endif
        @if(auth()->user()->canAccess('crm.delete'))
        <form action="{{ route('crm.candidats.destroy', $candidate) }}" method="POST" onsubmit="return confirm('Supprimer ce candidat et tout son historique ?')">
            @csrf @method('DELETE')
            <button type="submit" class="inline-flex items-center gap-2 bg-white border border-red-200 hover:bg-red-50 text-red-700 text-sm font-medium px-4 py-2 rounded-lg">Supprimer</button>
        </form>
        @endif
    </div>
</div>

<div class="border-b border-slate-200 mb-6">
    <nav class="flex gap-1 -mb-px overflow-x-auto">
        @foreach([
            'overview' => 'Vue d’ensemble',
            'notes' => 'Notes',
            'history' => 'Historique',
        ] as $key => $label)
            <a href="{{ route('crm.candidats.show', ['candidat' => $candidate, 'tab' => $key]) }}"
               class="px-4 py-2.5 text-sm font-medium whitespace-nowrap border-b-2 transition-colors {{ $tab === $key ? 'border-escm-primary text-escm-primary' : 'border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300' }}">
                {{ $label }}
            </a>
        @endforeach
    </nav>
</div>

@if($tab === 'overview')
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <h3 class="text-sm font-semibold text-slate-800 mb-4">Informations personnelles</h3>
        <dl class="space-y-3 text-sm">
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Genre</dt><dd class="text-slate-900 font-medium">{{ $candidate->genre_label }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Date de naissance</dt><dd class="text-slate-900 font-medium">{{ format_date($candidate->date_naissance) }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Téléphone</dt><dd class="text-slate-900 font-medium">{{ $candidate->telephone ?: '—' }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-slate-500">E-mail</dt><dd class="text-slate-900 font-medium break-all">{{ $candidate->email ?: '—' }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Adresse</dt><dd class="text-slate-900 font-medium text-right">{{ $candidate->adresse ?: '—' }}</dd></div>
        </dl>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <h3 class="text-sm font-semibold text-slate-800 mb-4">Informations académiques</h3>
        <dl class="space-y-3 text-sm">
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Programme intéressé</dt><dd class="text-slate-900 font-medium">{{ $candidate->programme ?: '—' }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Année / Intake</dt><dd class="text-slate-900 font-medium">{{ $candidate->annee_academique ?: '—' }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Niveau d’études</dt><dd class="text-slate-900 font-medium">{{ $candidate->niveau_etudes ?: '—' }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Établissement</dt><dd class="text-slate-900 font-medium text-right">{{ $candidate->etablissement_origine ?: '—' }}</dd></div>
        </dl>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 lg:col-span-2">
        <h3 class="text-sm font-semibold text-slate-800 mb-4">Statut CRM</h3>
        <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
            <div><dt class="text-slate-500 mb-1">Statut actuel</dt><dd><span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $candidate->statut_color }}">{{ $candidate->statut_label }}</span></dd></div>
            <div><dt class="text-slate-500 mb-1">Source</dt><dd class="text-slate-900 font-medium">{{ $candidate->source_label }}</dd></div>
            <div><dt class="text-slate-500 mb-1">Conseiller</dt><dd class="text-slate-900 font-medium">{{ $candidate->advisor?->name ?: 'Non assigné' }}</dd></div>
        </dl>
        @if($candidate->notes)
            <div class="mt-4 pt-4 border-t border-slate-100">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">Notes internes</p>
                <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ $candidate->notes }}</p>
            </div>
        @endif
    </div>
</div>
@endif

@if($tab === 'notes')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1">
        @if(auth()->user()->canAccess('crm.update'))
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 sticky top-4">
            <h3 class="text-sm font-semibold text-slate-800 mb-3">Ajouter une note</h3>
            <form action="{{ route('crm.candidats.notes.store', $candidate) }}" method="POST" class="space-y-3">
                @csrf
                <textarea name="content" rows="4" required maxlength="5000"
                          class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary"
                          placeholder="Écrire une note…">{{ old('content') }}</textarea>
                @error('content')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                <button type="submit" class="w-full bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-4 py-2.5 rounded-lg">Ajouter</button>
            </form>
        </div>
        @endif
    </div>
    <div class="lg:col-span-2 space-y-3">
        @forelse($candidate->crmNotes as $note)
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
            <div class="flex items-center justify-between gap-3 mb-2">
                <p class="text-sm font-medium text-slate-900">{{ $note->user?->name ?? 'Utilisateur' }}</p>
                <p class="text-xs text-slate-500">{{ $note->created_at?->format('d/m/Y H:i') }}</p>
            </div>
            <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ $note->content }}</p>
        </div>
        @empty
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-5 py-10 text-center text-slate-500 text-sm">
            Aucune note pour ce candidat.
        </div>
        @endforelse
    </div>
</div>
@endif

@if($tab === 'history')
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100">
        <h3 class="text-sm font-semibold text-slate-800">Historique des activités</h3>
    </div>
    <ul class="divide-y divide-slate-100">
        @forelse($candidate->activities as $activity)
        <li class="px-5 py-4 flex gap-4">
            <div class="mt-1 shrink-0">
                @php
                    $iconClass = match($activity->type) {
                        'created' => 'bg-blue-50 text-blue-600',
                        'status_changed' => 'bg-violet-50 text-violet-600',
                        'note_added' => 'bg-amber-50 text-amber-600',
                        default => 'bg-slate-100 text-slate-600',
                    };
                @endphp
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full {{ $iconClass }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <p class="text-sm font-medium text-slate-900">{{ $activity->title }}</p>
                    <p class="text-xs text-slate-500">{{ $activity->created_at?->format('d/m/Y H:i') }}</p>
                </div>
                @if($activity->description)
                    <p class="mt-0.5 text-sm text-slate-600">{{ $activity->description }}</p>
                @endif
                <p class="mt-1 text-xs text-slate-400">par {{ $activity->user?->name ?? 'Système' }}</p>
            </div>
        </li>
        @empty
        <li class="px-5 py-10 text-center text-slate-500 text-sm">Aucune activité enregistrée.</li>
        @endforelse
    </ul>
</div>
@endif
@endsection
