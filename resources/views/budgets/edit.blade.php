@php
    $title = 'Modifier le budget';
    $subtitle = \Carbon\Carbon::create($budget->annee, $budget->mois, 1)->locale('fr')->isoFormat('MMMM YYYY');
@endphp

@extends('layouts.app')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <form action="{{ route('budgets.update', $budget) }}" method="POST" class="space-y-5">
            @csrf @method('PUT')
            @include('budgets._form', ['edit' => true])
            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-5 py-2.5 rounded-lg">Enregistrer</button>
                <a href="{{ route('budgets.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
