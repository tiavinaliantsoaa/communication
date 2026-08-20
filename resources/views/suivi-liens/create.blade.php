@php
    $title = 'Nouveau lien';
    $subtitle = 'Créer un lien de redirection traçable';
@endphp

@extends('layouts.app')

@section('content')
<div class="max-w-lg">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <form action="{{ route('suivi-liens.store') }}" method="POST" class="space-y-5">
            @csrf
            @include('suivi-liens._form')
            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-5 py-2.5 rounded-lg">Créer le lien</button>
                <a href="{{ route('suivi-liens.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
