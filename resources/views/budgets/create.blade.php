@php
    $title = 'Nouveau budget';
    $subtitle = 'Définir un budget mensuel détaillé';
@endphp

@extends('layouts.app')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <form action="{{ route('budgets.store') }}" method="POST" class="space-y-5">
            @csrf
            @include('budgets._form')
            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-5 py-2.5 rounded-lg">Enregistrer</button>
                <a href="{{ route('budgets.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
