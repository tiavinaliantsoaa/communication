@php
    $input = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-escm-primary focus:ring-escm-primary';
@endphp

@extends('layouts.app')

@section('content')
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <h3 class="text-sm font-semibold text-slate-900 mb-4">Nouvel étudiant</h3>
        @if($groupes->isEmpty())
            <p class="text-sm text-slate-500">Ajoutez d’abord un groupe.</p>
        @else
            <form method="POST" action="{{ route('ecole.etudiants.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Prénom</label>
                    <input type="text" name="prenom" value="{{ old('prenom') }}" required maxlength="100" class="{{ $input }}">
                    @error('prenom')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Nom</label>
                    <input type="text" name="nom" value="{{ old('nom') }}" required maxlength="100" class="{{ $input }}">
                    @error('nom')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Groupe</label>
                    <select name="ecole_groupe_id" class="{{ $input }}" required>
                        @foreach($groupes as $groupe)
                            <option value="{{ $groupe->id }}" @selected((int) old('ecole_groupe_id') === (int) $groupe->id)>{{ $groupe->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-4 py-2.5 rounded-lg">Ajouter</button>
            </form>
        @endif
    </div>

    <div class="xl:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100">
                        <th class="px-5 py-3">Étudiant</th>
                        <th class="px-3 py-3">Groupe</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($etudiants as $etudiant)
                        <tr>
                            <td class="px-5 py-3 font-medium text-slate-900">{{ $etudiant->nom_complet }}</td>
                            <td class="px-3 py-3 text-slate-600">{{ $etudiant->groupe->nom ?? '—' }}</td>
                            <td class="px-5 py-3 text-right">
                                <form method="POST" action="{{ route('ecole.etudiants.destroy', $etudiant) }}" onsubmit="return confirm('Supprimer cet étudiant ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-slate-500 hover:text-red-600">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-8 text-center text-slate-500">Aucun étudiant pour le moment.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
