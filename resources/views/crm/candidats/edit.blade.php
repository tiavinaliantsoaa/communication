@php
    $title = 'Modifier le candidat';
    $subtitle = $candidate->full_name;
@endphp

@extends('layouts.app')

@section('content')
<div class="max-w-3xl">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <form action="{{ route('crm.candidats.update', $candidate) }}" method="POST">
            @csrf
            @method('PUT')
            @include('crm.candidats._form', ['candidate' => $candidate])
            <div class="flex items-center gap-3 pt-6 mt-2 border-t border-slate-100">
                <button type="submit" class="bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-5 py-2.5 rounded-lg">Enregistrer</button>
                <a href="{{ route('crm.candidats.show', $candidate) }}" class="text-sm text-slate-600 hover:text-slate-900">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
