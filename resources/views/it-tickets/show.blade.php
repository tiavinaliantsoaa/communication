@php
    $title = 'Ticket #'.$ticket->id;
    $subtitle = $ticket->titre;
    $steps = [
        'en_attente' => ['label' => 'En attente', 'hint' => 'Requête reçue'],
        'en_cours' => ['label' => 'En cours', 'hint' => 'Prise en charge'],
        'corrige' => ['label' => 'Corrigé', 'hint' => 'Problème résolu'],
        'annule' => ['label' => 'Annulé', 'hint' => 'Requête annulée'],
    ];
@endphp

@extends('layouts.app')

@section('content')
<div class="mb-4">
    <a href="{{ route('it-tickets.index') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Retour à la liste
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                <div>
                    <p class="text-xs font-mono text-slate-400 mb-1">#{{ $ticket->id }}</p>
                    <h2 class="text-lg font-semibold text-slate-900">{{ $ticket->titre }}</h2>
                </div>
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $ticket->statut_color }}">
                    {{ $ticket->statut_label }}
                </span>
            </div>

            <div class="prose prose-sm max-w-none text-slate-700 whitespace-pre-wrap">{{ $ticket->description }}</div>

            <dl class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm border-t border-slate-100 pt-4">
                <div>
                    <dt class="text-slate-500">Demandeur</dt>
                    <dd class="font-medium text-slate-900">{{ $ticket->user?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Créé le</dt>
                    <dd class="font-medium text-slate-900">{{ $ticket->created_at?->locale('fr')->isoFormat('D MMMM YYYY, HH:mm') }}</dd>
                </div>
                @if($ticket->statut_updated_at)
                <div>
                    <dt class="text-slate-500">Statut mis à jour</dt>
                    <dd class="font-medium text-slate-900">{{ $ticket->statut_updated_at->locale('fr')->isoFormat('D MMM YYYY, HH:mm') }}</dd>
                </div>
                @endif
                @if($ticket->updatedBy)
                <div>
                    <dt class="text-slate-500">Par</dt>
                    <dd class="font-medium text-slate-900">{{ $ticket->updatedBy->name }}</dd>
                </div>
                @endif
            </dl>

            @if($ticket->admin_notes)
            <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Notes IT</p>
                <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ $ticket->admin_notes }}</p>
            </div>
            @endif
        </div>

        {{-- Suivi des étapes --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h3 class="text-sm font-semibold text-slate-900 mb-4">Suivi de la requête</h3>
            <ol class="space-y-3">
                @foreach($steps as $key => $step)
                    @php
                        $isCurrent = $ticket->statut === $key;
                        $order = array_keys($steps);
                        $currentIdx = array_search($ticket->statut, $order, true);
                        $stepIdx = array_search($key, $order, true);
                        // "annule" is terminal alternative to corrige — don't mark corrige as passed if cancelled
                        $isDone = false;
                        if ($ticket->statut === 'annule') {
                            $isDone = in_array($key, ['en_attente', 'annule'], true) && $key !== 'annule' ? ($key === 'en_attente') : false;
                            if ($key === 'en_attente') $isDone = true;
                        } elseif ($ticket->statut === 'corrige') {
                            $isDone = $stepIdx < $currentIdx;
                        } elseif (in_array($ticket->statut, ['en_attente', 'en_cours'], true)) {
                            $isDone = $stepIdx < $currentIdx && $key !== 'annule';
                        }
                    @endphp
                    <li class="flex items-start gap-3">
                        <span @class([
                            'mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[11px] font-bold',
                            'bg-escm-primary text-white' => $isCurrent,
                            'bg-green-100 text-green-700' => $isDone && ! $isCurrent,
                            'bg-slate-100 text-slate-400' => ! $isCurrent && ! $isDone,
                        ])>
                            @if($isDone && ! $isCurrent)
                                ✓
                            @else
                                {{ $stepIdx + 1 }}
                            @endif
                        </span>
                        <div>
                            <p @class([
                                'text-sm font-medium',
                                'text-escm-primary' => $isCurrent,
                                'text-slate-900' => $isDone && ! $isCurrent,
                                'text-slate-500' => ! $isCurrent && ! $isDone,
                            ])>{{ $step['label'] }}</p>
                            <p class="text-xs text-slate-500">{{ $step['hint'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </div>

    <div class="space-y-6">
        @if($canManage)
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6" x-data="{ statut: @js(old('statut', $ticket->statut)) }">
            <h3 class="text-sm font-semibold text-slate-900 mb-1">Évaluation du ticket</h3>
            <p class="text-xs text-slate-500 mb-4">Réservé au Super Admin — cochez l’étape actuelle.</p>

            <form action="{{ route('it-tickets.status', $ticket) }}" method="POST" class="space-y-4">
                @csrf
                @method('PATCH')

                <div class="space-y-2">
                    @foreach($statuts as $key => $label)
                    <label class="flex items-center gap-3 rounded-lg border px-3 py-2.5 cursor-pointer transition-colors"
                           :class="statut === '{{ $key }}' ? 'border-escm-primary bg-blue-50/50' : 'border-slate-200 hover:bg-slate-50'">
                        <input type="radio" name="statut" value="{{ $key }}" x-model="statut"
                               class="text-escm-primary focus:ring-escm-primary border-slate-300">
                        <span class="text-sm font-medium text-slate-800">{{ $label }}</span>
                    </label>
                    @endforeach
                </div>
                @error('statut')<p class="text-xs text-red-600">{{ $message }}</p>@enderror

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Notes IT <span class="text-slate-400 font-normal">(optionnel)</span></label>
                    <textarea name="admin_notes" rows="3" maxlength="5000"
                              class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary"
                              placeholder="Commentaire visible par le demandeur…">{{ old('admin_notes', $ticket->admin_notes) }}</textarea>
                    @error('admin_notes')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="w-full bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-4 py-2.5 rounded-lg">
                    Enregistrer le statut
                </button>
            </form>
        </div>
        @endif

        @if(auth()->user()?->isSuperAdmin() || ((int) $ticket->user_id === (int) auth()->id() && $ticket->statut === 'en_attente'))
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h3 class="text-sm font-semibold text-slate-900 mb-3">Actions</h3>
            <form action="{{ route('it-tickets.destroy', $ticket) }}" method="POST" onsubmit="return confirm('Supprimer ce ticket ?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 border border-red-200 text-red-600 hover:bg-red-50 text-sm font-medium px-4 py-2.5 rounded-lg">
                    Supprimer le ticket
                </button>
            </form>
        </div>
        @endif
    </div>
</div>
@endsection
