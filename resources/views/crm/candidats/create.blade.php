@php
    $title = 'Nouveau candidat';
    $subtitle = 'Créer une fiche prospect';
@endphp

@extends('layouts.app')

@section('content')
<div class="max-w-3xl">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <form action="{{ route('crm.candidats.store') }}" method="POST">
            @csrf
            @include('crm.candidats._form')
            <div class="flex items-center gap-3 pt-6 mt-2 border-t border-slate-100">
                <button type="submit" class="bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-5 py-2.5 rounded-lg">Créer le candidat</button>
                <a href="{{ route('crm.candidats.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
