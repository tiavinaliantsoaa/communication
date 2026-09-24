@php
    $title = 'Modifier la dépense';
    $subtitle = $depense->objet;
@endphp

@extends('layouts.app')

@section('content')
<div class="max-w-2xl space-y-4" x-data="{ openCat: {{ $errors->has('categorie_nom') ? 'true' : 'false' }} }" @toggle-categorie.window="openCat = !openCat">
    <form x-show="openCat" x-cloak action="{{ route('depenses.categories.store') }}" method="POST" class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 flex flex-col gap-3 sm:flex-row sm:items-end">
        @csrf
        <div class="flex-1">
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Nouvelle catégorie</label>
            <input type="text" name="categorie_nom" value="{{ old('categorie_nom') }}" required maxlength="100" placeholder="Ex. Transport"
                   class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
            @error('categorie_nom')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-4 py-2.5 rounded-lg">Ajouter</button>
    </form>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <form action="{{ route('depenses.update', $depense) }}" method="POST" class="space-y-5">
            @csrf @method('PUT')
            @include('depenses._form', ['edit' => true])
            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-5 py-2.5 rounded-lg">Enregistrer</button>
                <a href="{{ route('depenses.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
