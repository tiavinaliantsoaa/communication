@php
    $title = 'Résultat — '.$invitation->candidat_nom;
    $subtitle = $invitation->test?->nom;
@endphp

@extends('layouts.app')

@section('content')
<div class="mb-4">
    <a href="{{ route('crm.online-tests.results.index') }}" class="text-sm text-slate-500 hover:text-slate-800">← Retour aux résultats</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <p class="text-xs uppercase tracking-wider text-slate-500 font-semibold">Candidat</p>
        <p class="mt-1 font-semibold text-slate-900">{{ $invitation->candidat_nom }}</p>
        @if($invitation->candidat_email)<p class="text-sm text-slate-500">{{ $invitation->candidat_email }}</p>@endif
    </div>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <p class="text-xs uppercase tracking-wider text-slate-500 font-semibold">Statut</p>
        <p class="mt-1">
            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $invitation->statut_color }}">{{ $invitation->statut_label }}</span>
        </p>
        @if($invitation->cancel_reason)
            <p class="mt-2 text-xs text-red-600">{{ $invitation->cancel_reason }}</p>
        @endif
    </div>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <p class="text-xs uppercase tracking-wider text-slate-500 font-semibold">Score</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">
            {{ $invitation->score_final !== null ? rtrim(rtrim(number_format((float)$invitation->score_final, 2, ',', ' '), '0'), ',') : '—' }}
        </p>
        <p class="text-xs text-slate-500">
            @if($invitation->started_at) Début {{ $invitation->started_at->format('d/m/Y H:i') }} @endif
            @if($invitation->submitted_at) · Remis {{ $invitation->submitted_at->format('d/m/Y H:i') }} @endif
        </p>
    </div>
</div>

@if($invitation->statut === 'termine' && auth()->user()->canAccess('online_tests.update'))
<form action="{{ route('crm.online-tests.results.grade', $invitation) }}" method="POST" class="space-y-4">
    @csrf
@endif

<div class="space-y-3">
    @foreach($invitation->test->questions as $i => $question)
        @php $answer = $answersByQuestion->get($question->id); @endphp
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Question {{ $i + 1 }} · {{ $question->type_label }} · max {{ rtrim(rtrim(number_format((float)$question->points, 2, ',', ' '), '0'), ',') }} pts</p>
            <p class="text-sm font-medium text-slate-900 whitespace-pre-wrap mb-3">{{ $question->enonce }}</p>
            <div class="rounded-lg bg-slate-50 border border-slate-100 px-3 py-2 mb-3">
                <p class="text-[11px] font-semibold uppercase text-slate-500 mb-1">Réponse du candidat</p>
                <p class="text-sm text-slate-800 whitespace-pre-wrap">{{ $answer?->reponse ?: '—' }}</p>
            </div>
            @if($invitation->statut === 'termine' && auth()->user()->canAccess('online_tests.update'))
                <div class="flex items-center gap-3">
                    <label class="text-xs font-medium text-slate-600">Points attribués</label>
                    <input type="number" name="points[{{ $question->id }}]" min="0" max="{{ $question->points }}" step="0.5"
                           value="{{ old('points.'.$question->id, $answer?->points_attribues) }}"
                           class="w-28 rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
                    <span class="text-xs text-slate-400">/ {{ rtrim(rtrim(number_format((float)$question->points, 2, ',', ' '), '0'), ',') }}</span>
                </div>
            @elseif($answer?->points_attribues !== null)
                <p class="text-xs text-slate-600">Points : <strong>{{ rtrim(rtrim(number_format((float)$answer->points_attribues, 2, ',', ' '), '0'), ',') }}</strong></p>
            @endif
        </div>
    @endforeach
</div>

@if($invitation->statut === 'termine' && auth()->user()->canAccess('online_tests.update'))
    <div class="mt-6 bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Commentaire correcteur</label>
            <textarea name="commentaire_correcteur" rows="3" maxlength="5000"
                      class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">{{ old('commentaire_correcteur', $invitation->commentaire_correcteur) }}</textarea>
        </div>
        <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
            <input type="checkbox" name="valide" value="1" class="rounded border-slate-300 text-escm-primary focus:ring-escm-primary" @checked(old('valide', $invitation->valide))>
            <span>Valider le résultat</span>
        </label>
        <button type="submit" class="bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-5 py-2.5 rounded-lg">Enregistrer les notes</button>
    </div>
</form>
@elseif($invitation->commentaire_correcteur)
    <div class="mt-6 bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <p class="text-xs font-semibold uppercase text-slate-500 mb-1">Commentaire correcteur</p>
        <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ $invitation->commentaire_correcteur }}</p>
    </div>
@endif
@endsection
