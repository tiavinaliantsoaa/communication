@php $title = 'Test annulé'; @endphp
@extends('layouts.online-test')

@section('content')
<div class="mx-auto max-w-lg px-4 py-16 text-center">
    <div class="bg-white rounded-xl border border-red-200 shadow-sm p-8">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-red-50 text-red-600">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
        </div>
        <h1 class="text-xl font-semibold text-slate-900 mb-2">Test annulé</h1>
        <p class="text-sm text-slate-600 mb-4">
            Votre test a été annulé en raison d’une <strong>action suspecte détectée</strong>.
        </p>
        @if($invitation->cancel_reason)
            <p class="text-sm text-red-700 bg-red-50 border border-red-100 rounded-lg px-3 py-2 mb-4">
                Motif : {{ $invitation->cancel_reason }}
            </p>
        @endif
        <p class="text-xs text-slate-500">
            Ce lien était à usage unique et ne peut plus être réutilisé. Contactez l’administration ESCM si besoin.
        </p>
    </div>
</div>
@endsection
