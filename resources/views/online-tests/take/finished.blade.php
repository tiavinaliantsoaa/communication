@php $title = 'Test terminé'; @endphp
@extends('layouts.online-test')

@section('content')
<div class="mx-auto max-w-lg px-4 py-16 text-center">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-8">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-green-50 text-green-700">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h1 class="text-xl font-semibold text-slate-900 mb-2">
            {{ $invitation->statut === 'expire' ? 'Temps écoulé' : 'Test soumis' }}
        </h1>
        <p class="text-sm text-slate-600 mb-2">
            Merci {{ $invitation->candidat_nom }}. Vos réponses ont été enregistrées.
        </p>
        <p class="text-xs text-slate-500">Vous pouvez fermer cette page. Les résultats seront communiqués par l’équipe ESCM.</p>
    </div>
</div>
@endsection
