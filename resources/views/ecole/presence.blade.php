@extends('layouts.app')

@section('content')
<div class="rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-5 py-4">
        <h2 class="text-base font-semibold text-slate-900">
            Enregistrer une présence{{ $seance?->groupe ? ': '.$seance->groupe->nom : '' }}
        </h2>
        @if($seance)
            <p class="mt-1 text-sm text-slate-500">
                {{ $seance->matiere }} · {{ $seance->date?->locale('fr')->isoFormat('ddd D MMM') }}
                · {{ \App\Support\EcoleTimetable::label($seance->heure('heure_debut')) }} – {{ \App\Support\EcoleTimetable::label($seance->heure('heure_fin')) }}
            </p>
        @endif
    </div>

    @if($seances->isNotEmpty())
        <div class="flex flex-wrap gap-2 px-5 py-3">
            @foreach($seances as $ligne)
                <a href="{{ route('ecole.presence.index', ['seance' => $ligne->id]) }}"
                   class="rounded-lg border px-3 py-1.5 text-xs font-semibold {{ $seance && (int) $seance->id === (int) $ligne->id ? 'border-escm-primary bg-blue-50 text-escm-primary' : 'border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                    {{ $ligne->date?->locale('fr')->isoFormat('D MMM') }}
                    {{ \App\Support\EcoleTimetable::label($ligne->heure('heure_debut')) }}
                </a>
            @endforeach
        </div>
    @endif

    @if(! $seance)
        <p class="px-5 py-10 text-center text-sm text-slate-500">Aucun cours prévu dans les 24 prochaines heures.</p>
    @else
        @unless($editable)
            <p class="mx-5 mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Ce cours est déjà programmé. La présence pourra être enregistrée à partir de {{ $ouverture?->locale('fr')->isoFormat('dddd D MMM [à] HH[h]mm') }}.
            </p>
        @endunless

        <form method="POST" action="{{ route('ecole.presence.store') }}" class="px-5 pb-6">
            @csrf
            <input type="hidden" name="ecole_seance_id" value="{{ $seance->id }}">
            @error('presences')<p class="mb-4 text-sm text-red-600">{{ $message }}</p>@enderror
            <div class="grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-4">
                @foreach($etudiants as $etudiant)
                    @php
                        $presence = $presences->get($etudiant->id);
                        $statut = old('presences.'.$etudiant->id.'.statut', $presence?->statut);
                        $jours = $absences->get($etudiant->id, collect())->map(fn ($ligne) => $ligne->seance?->date?->toDateString())->filter()->unique()->count();
                    @endphp
                    <div class="text-center" x-data="{ statut: '{{ $statut }}', minutes: '{{ (int) old('presences.'.$etudiant->id.'.minutes', $presence?->minutes_retard) }}', motif: '' }" x-init="motif = $refs.motif.value">
                        <div class="mx-auto flex h-20 w-16 items-end justify-center overflow-hidden rounded border border-slate-200 bg-slate-200">
                            <svg viewBox="0 0 64 80" class="h-full w-full text-white" fill="currentColor" aria-hidden="true">
                                <circle cx="32" cy="22" r="12"/>
                                <path d="M8 80c2-20 12-30 24-30s22 10 24 30"/>
                            </svg>
                        </div>
                        <p class="mt-2 text-xs font-semibold leading-4 text-red-600">{{ $etudiant->nom_complet }}</p>
                        <p class="mt-1 text-[11px] text-slate-500">{{ $jours }} {{ $jours > 1 ? 'jours absents' : 'jour absent' }}</p>
                        <input type="hidden" name="presences[{{ $etudiant->id }}][statut]" :value="statut">
                        <div class="mt-3 space-y-1 text-left text-xs text-slate-700 {{ $editable ? '' : 'opacity-60' }}">
                            @foreach(['present' => 'Présent', 'absent' => 'Absent', 'retard' => 'En retard'] as $valeur => $label)
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" class="rounded border-slate-300 text-escm-primary focus:ring-escm-primary" :checked="statut === '{{ $valeur }}'" @change="statut = $event.target.checked ? '{{ $valeur }}' : ''" @disabled(! $editable)>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        <div x-show="statut === 'retard'" x-cloak class="mt-2 space-y-2 text-left">
                            <input type="number" name="presences[{{ $etudiant->id }}][minutes]" x-model="minutes" min="1" max="300" placeholder="Minutes" class="w-full rounded-lg border-slate-300 text-xs shadow-sm focus:border-escm-primary focus:ring-escm-primary" @disabled(! $editable)>
                            <input x-ref="motif" type="text" name="presences[{{ $etudiant->id }}][motif]" x-model="motif" value="{{ old('presences.'.$etudiant->id.'.motif', $presence?->motif) }}" maxlength="255" placeholder="Motif du retard" class="w-full rounded-lg border-slate-300 text-xs shadow-sm focus:border-escm-primary focus:ring-escm-primary" @disabled(! $editable)>
                        </div>
                    </div>
                @endforeach
            </div>
            @if($editable)
                <button type="submit" class="mt-6 bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-4 py-2.5 rounded-lg">Enregistrer la présence</button>
            @endif
        </form>
    @endif
</div>
@endsection
