@php
    $input = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-escm-primary focus:ring-escm-primary';
@endphp

@extends('layouts.app')

@section('content')
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <h3 class="text-sm font-semibold text-slate-900 mb-4">Ajouter un créneau</h3>
        @if($groupes->isEmpty())
            <p class="text-sm text-slate-500">Ajoutez d’abord un groupe.</p>
        @else
            <form method="POST" action="{{ route('ecole.emplois.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Groupe</label>
                    <select name="ecole_groupe_id" class="{{ $input }}" required>
                        @foreach($groupes as $ligne)
                            <option value="{{ $ligne->id }}" @selected((int) old('ecole_groupe_id', $groupe?->id) === (int) $ligne->id)>{{ $ligne->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Jour</label>
                    <select name="jour" class="{{ $input }}" required>
                        @foreach($jours as $numero => $label)
                            <option value="{{ $numero }}" @selected((int) old('jour', 1) === (int) $numero)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Début</label>
                        <input type="time" name="heure_debut" value="{{ old('heure_debut', '08:00') }}" min="08:00" max="17:00" required class="{{ $input }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Fin</label>
                        <input type="time" name="heure_fin" value="{{ old('heure_fin', '09:00') }}" min="08:00" max="17:00" required class="{{ $input }}">
                    </div>
                </div>
                @error('heure_debut')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Matière</label>
                    <input type="text" name="matiere" value="{{ old('matiere') }}" required maxlength="120" class="{{ $input }}" placeholder="Ex. COM & PLAN">
                    @error('matiere')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Salle</label>
                    <input type="text" name="salle" value="{{ old('salle') }}" maxlength="120" class="{{ $input }}" placeholder="Ex. Grasse">
                </div>
                <button type="submit" class="bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-4 py-2.5 rounded-lg">Enregistrer</button>
                <p class="text-xs text-slate-500">Le créneau se répète chaque semaine, entre 08h et 17h, et s’affiche sur le dashboard du groupe.</p>
            </form>
        @endif
    </div>

    <div class="xl:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
            <h3 class="text-sm font-semibold text-slate-900">Créneaux{{ $groupe ? ' — '.$groupe->nom : '' }}</h3>
            @if($groupes->isNotEmpty())
                <form method="GET" action="{{ route('ecole.emplois.index') }}">
                    <label class="sr-only" for="filtre-groupe">Groupe</label>
                    <select id="filtre-groupe" name="groupe" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-escm-primary focus:ring-escm-primary">
                        @foreach($groupes as $ligne)
                            <option value="{{ $ligne->id }}" @selected($groupe && (int) $groupe->id === (int) $ligne->id)>{{ $ligne->nom }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100">
                        <th class="px-5 py-3">Jour</th>
                        <th class="px-3 py-3">Horaire</th>
                        <th class="px-3 py-3">Matière</th>
                        <th class="px-3 py-3">Salle</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($seances as $seance)
                        <tr>
                            <td class="px-5 py-3 font-medium text-slate-900">{{ $jours[$seance->jour] ?? $seance->jour }}</td>
                            <td class="px-3 py-3 text-slate-600 whitespace-nowrap">{{ \App\Support\EcoleTimetable::label($seance->heure('heure_debut')) }} – {{ \App\Support\EcoleTimetable::label($seance->heure('heure_fin')) }}</td>
                            <td class="px-3 py-3 text-slate-800">{{ $seance->matiere }}</td>
                            <td class="px-3 py-3 text-slate-600">{{ $seance->salle ?: '—' }}</td>
                            <td class="px-5 py-3 text-right">
                                <form method="POST" action="{{ route('ecole.emplois.destroy', $seance) }}" onsubmit="return confirm('Supprimer ce créneau ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-slate-500 hover:text-red-600">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-slate-500">Aucun emploi du temps pour le moment.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
