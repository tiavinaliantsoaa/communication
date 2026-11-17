@php
    $input = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-escm-primary focus:ring-escm-primary';
    $naissance = old('date_naissance', optional($etudiant->date_naissance ?: $etudiant->candidat?->date_naissance)->toDateString());
@endphp

@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-3xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <h3 class="mb-4 text-sm font-semibold text-slate-900">Modifier la fiche</h3>
    <form method="POST" action="{{ route('ecole.etudiants.update', $etudiant) }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        @csrf
        @method('PUT')
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Prénom</label>
            <input type="text" name="prenom" value="{{ old('prenom', $etudiant->prenom) }}" required maxlength="100" class="{{ $input }}">
            @error('prenom')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Nom</label>
            <input type="text" name="nom" value="{{ old('nom', $etudiant->nom) }}" required maxlength="100" class="{{ $input }}">
            @error('nom')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Nom complet</label>
            <input type="text" name="nom_officiel" value="{{ old('nom_officiel', $etudiant->nom_officiel) }}" maxlength="150" class="{{ $input }}">
            @error('nom_officiel')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Nom en caractères</label>
            <input type="text" name="nom_caracteres" value="{{ old('nom_caracteres', $etudiant->nom_caracteres) }}" maxlength="150" class="{{ $input }}">
            @error('nom_caracteres')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Palier</label>
            <input type="text" name="palier" value="{{ old('palier', $etudiant->palier ?: $etudiant->candidat?->niveau_etudes ?: $etudiant->candidat?->programme) }}" maxlength="80" placeholder="Ex. Licence 1" class="{{ $input }}">
            @error('palier')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Groupe-repère</label>
            <select name="ecole_groupe_id" class="{{ $input }}" required>
                @foreach($groupes as $groupe)
                    <option value="{{ $groupe->id }}" @selected((int) old('ecole_groupe_id', $etudiant->ecole_groupe_id) === (int) $groupe->id)>{{ $groupe->nom }}</option>
                @endforeach
            </select>
            @error('ecole_groupe_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Nom d’utilisateur</label>
            <input type="text" name="nom_utilisateur" value="{{ old('nom_utilisateur', $etudiant->nom_utilisateur) }}" maxlength="80" class="{{ $input }}">
            @error('nom_utilisateur')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Date de naissance</label>
            <input type="date" name="date_naissance" value="{{ $naissance }}" class="{{ $input }}">
            @error('date_naissance')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Site web</label>
            <input type="text" name="site_web" value="{{ old('site_web', $etudiant->site_web) }}" maxlength="255" class="{{ $input }}">
            @error('site_web')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Courriel</label>
            <input type="email" name="email" value="{{ old('email', $etudiant->email ?: $etudiant->candidat?->email) }}" maxlength="255" class="{{ $input }}">
            @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Code de verrouillage</label>
            <input type="text" name="code_verrouillage" value="{{ old('code_verrouillage', $etudiant->code_verrouillage) }}" maxlength="40" class="{{ $input }}">
            @error('code_verrouillage')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Matricule de l’élève</label>
            <input type="text" name="matricule" value="{{ old('matricule', $etudiant->matricule) }}" maxlength="40" placeholder="Ex. E26-011" class="{{ $input }}">
            @error('matricule')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="sm:col-span-2">
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Domicile</label>
            <input type="text" name="domicile" value="{{ old('domicile', $etudiant->domicile ?: $etudiant->candidat?->adresse) }}" maxlength="255" class="{{ $input }}">
            @error('domicile')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="flex items-center gap-3 sm:col-span-2">
            <button type="submit" class="rounded-lg bg-escm-primary px-4 py-2.5 text-sm font-medium text-white hover:bg-escm-primary-dark">Enregistrer</button>
            <a href="{{ route('ecole.etudiants.show', $etudiant) }}" class="text-sm font-medium text-slate-500 hover:text-slate-800">Annuler</a>
        </div>
    </form>
</div>
@endsection
