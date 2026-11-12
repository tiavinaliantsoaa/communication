@php
    $input = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-escm-primary focus:ring-escm-primary';
@endphp

@extends('layouts.app')

@section('content')
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <h3 class="text-sm font-semibold text-slate-900 mb-4">Nouveau pointage</h3>
        @if($etudiants->isEmpty())
            <p class="text-sm text-slate-500">Ajoutez d’abord un étudiant.</p>
        @else
            <form method="POST" action="{{ route('ecole.absences.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Étudiant</label>
                    <select name="ecole_etudiant_id" class="{{ $input }}" required>
                        @foreach($etudiants as $etudiant)
                            <option value="{{ $etudiant->id }}" @selected((int) old('ecole_etudiant_id') === (int) $etudiant->id)>{{ $etudiant->nom_complet }} — {{ $etudiant->groupe->nom ?? '—' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Date</label>
                    <input type="date" name="date" value="{{ old('date', $dateParDefaut) }}" required class="{{ $input }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Statut</label>
                    <select name="statut" class="{{ $input }}" required>
                        @foreach($statuts as $value => $label)
                            <option value="{{ $value }}" @selected(old('statut', 'absent') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-4 py-2.5 rounded-lg">Enregistrer</button>
            </form>
        @endif
    </div>

    <div class="xl:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100">
                        <th class="px-5 py-3">Date</th>
                        <th class="px-3 py-3">Étudiant</th>
                        <th class="px-3 py-3">Groupe</th>
                        <th class="px-3 py-3">Statut</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($assiduites as $assiduite)
                        <tr>
                            <td class="px-5 py-3 text-slate-600 whitespace-nowrap">{{ $assiduite->date->format('d/m/Y') }}</td>
                            <td class="px-3 py-3 font-medium text-slate-900">{{ $assiduite->etudiant->nom_complet ?? '—' }}</td>
                            <td class="px-3 py-3 text-slate-600">{{ $assiduite->etudiant->groupe->nom ?? '—' }}</td>
                            <td class="px-3 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold
                                    {{ $assiduite->statut === 'present' ? 'bg-green-100 text-green-700' : '' }}
                                    {{ $assiduite->statut === 'absent' ? 'bg-red-100 text-red-700' : '' }}
                                    {{ $assiduite->statut === 'retard' ? 'bg-amber-50 text-amber-700' : '' }}
                                ">{{ $assiduite->statut_label }}</span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <form method="POST" action="{{ route('ecole.absences.destroy', $assiduite) }}" onsubmit="return confirm('Supprimer ce pointage ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-slate-500 hover:text-red-600">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-slate-500">Aucun retard ni absence pour le moment.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection