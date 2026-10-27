@php
    $title = 'Nouvelle requête IT';
    $subtitle = 'Décrivez votre problème ou besoin informatique';
@endphp

@extends('layouts.app')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <form action="{{ route('it-tickets.store') }}" method="POST" class="space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Titre <span class="text-red-500">*</span></label>
                <input type="text" name="titre" value="{{ old('titre') }}" required maxlength="255"
                       class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary"
                       placeholder="Ex. Ordinateur qui ne démarre plus">
                @error('titre')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Description <span class="text-red-500">*</span></label>
                <textarea name="description" rows="6" required maxlength="5000"
                          class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary"
                          placeholder="Décrivez le problème, le matériel concerné, depuis quand, etc.">{{ old('description') }}</textarea>
                @error('description')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Résultat attendu <span class="text-red-500">*</span></label>
                <textarea name="resultat_attendu" rows="4" required maxlength="5000"
                          class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary"
                          placeholder="Décrivez le résultat souhaité une fois le problème résolu…">{{ old('resultat_attendu') }}</textarea>
                @error('resultat_attendu')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Impact du problème <span class="text-red-500">*</span></label>
                <select name="impact" required
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
                    <option value="">— Sélectionner —</option>
                    @foreach($impacts as $key => $label)
                        <option value="{{ $key }}" @selected(old('impact') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('impact')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <p class="text-xs text-slate-500">La requête démarre en statut <strong>En attente</strong>. Vous pourrez suivre son avancement (En cours → Corrigé / Annulé).</p>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-5 py-2.5 rounded-lg">Envoyer la requête</button>
                <a href="{{ route('it-tickets.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
