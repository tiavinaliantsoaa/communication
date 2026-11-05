@php
    $title = $test->nom;
    $subtitle = 'Fiche du test';
@endphp

@extends('layouts.app')

@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <a href="{{ route('crm.online-tests.index') }}" class="text-sm text-slate-500 hover:text-slate-800">← Retour aux tests</a>
    <div class="flex items-center gap-2">
        <a href="{{ route('crm.online-tests.questions.index', $test) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Questions</a>
        @if(auth()->user()->canAccess('online_tests.update'))
            <a href="{{ route('crm.online-tests.edit', $test) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-escm-primary px-3 py-2 text-sm font-medium text-white hover:bg-escm-primary-dark">Modifier</a>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
        <div class="flex flex-wrap items-center gap-2">
            <h2 class="text-lg font-semibold text-slate-900">{{ $test->nom }}</h2>
            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $test->actif ? 'bg-green-50 text-green-700 ring-green-600/20' : 'bg-slate-100 text-slate-600 ring-slate-500/20' }}">
                {{ $test->actif ? 'Actif' : 'Inactif' }}
            </span>
        </div>
        @if($test->description)
            <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ $test->description }}</p>
        @endif
        <dl class="grid grid-cols-2 gap-4 text-sm border-t border-slate-100 pt-4">
            <div>
                <dt class="text-slate-500">Durée</dt>
                <dd class="font-medium text-slate-900">{{ $test->duree_minutes }} min</dd>
            </div>
            <div>
                <dt class="text-slate-500">Questions</dt>
                <dd class="font-medium text-slate-900">{{ $test->questions->count() }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Points totaux</dt>
                <dd class="font-medium text-slate-900">{{ rtrim(rtrim(number_format($test->pointsTotal(), 2, ',', ' '), '0'), ',') }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Liens générés</dt>
                <dd class="font-medium text-slate-900">{{ $test->invitations_count }}</dd>
            </div>
        </dl>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <h3 class="text-sm font-semibold text-slate-800 mb-3">Actions rapides</h3>
        <div class="space-y-2">
            <a href="{{ route('crm.online-tests.questions.index', $test) }}" class="block w-full text-center rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Gérer les questions</a>
            @if(auth()->user()->canAccess('online_tests.create'))
                <a href="{{ route('crm.online-tests.links.create', ['test' => $test->id]) }}" class="block w-full text-center rounded-lg bg-escm-primary px-3 py-2 text-sm font-medium text-white hover:bg-escm-primary-dark">Générer un lien candidat</a>
            @endif
        </div>
    </div>
</div>
@endsection
