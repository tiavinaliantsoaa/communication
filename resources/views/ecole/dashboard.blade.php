@extends('layouts.app')

@section('content')
<div class="grid grid-cols-1 sm:grid-cols-2 {{ $heuresParent ? 'lg:grid-cols-3' : 'xl:grid-cols-4' }} gap-4 mb-6">
    @if($heuresParent)
        <x-kpi-card label="Total heure de cours" :value="$heuresParent['cours']" :subtext="$heuresParent['groupes']" icon-color="blue">
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-kpi-card>
        <x-kpi-card label="Total heure absence" :value="$heuresParent['absence']" subtext="Cours des jours d’absence" icon-color="purple">
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-kpi-card>
        <x-kpi-card label="Total heure retard" :value="$heuresParent['retard']" subtext="Cours des jours de retard" icon-color="green">
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-kpi-card>
    @else
    <x-kpi-card label="Nombre étudiant" :value="$nbEtudiants" :subtext="$nbGroupes.' '.($nbGroupes > 1 ? 'groupes' : 'groupe')" icon-color="blue">
        <x-slot:icon>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </x-slot:icon>
    </x-kpi-card>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <div class="flex items-start justify-between mb-3">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Etudiants par groupe</p>
            <div class="p-2 rounded-lg bg-purple-50 text-purple-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
        </div>
        @if($groupes->isEmpty())
            <p class="text-2xl font-bold text-slate-900 mb-1">0</p>
            <p class="text-xs text-slate-500">Aucun groupe</p>
        @else
            <div class="flex flex-wrap gap-2 overflow-y-auto" style="max-height: 7rem;">
                @foreach($groupes as $ligne)
                    <span class="inline-flex items-center rounded-lg bg-slate-50 border border-slate-200 px-2.5 py-1 text-sm font-bold text-slate-900">{{ $ligne->etudiants_count }} {{ $ligne->nom }}</span>
                @endforeach
            </div>
        @endif
    </div>

    <x-kpi-card label="Statistique présence" :value="$pctPresence.' %'" :subtext="$presenceLabel" icon-color="green" :progress="$pctPresence" :progress-label="$pctPresence.' % de présents'" progress-color="green">
        <x-slot:icon>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </x-slot:icon>
    </x-kpi-card>

    <x-kpi-card label="Statistique absence" :value="$pctAbsence.' %'" :subtext="$absenceLabel" icon-color="red" :progress="$pctAbsence" :progress-label="$pctAbsence.' % d’absents'" progress-color="red">
        <x-slot:icon>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </x-slot:icon>
    </x-kpi-card>
    @endif
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 border-b border-slate-100">
        <div>
            <h3 class="text-sm font-semibold text-slate-900">Emploi du temps{{ $enseignantVu ? ' — '.$enseignantVu->nom_complet : ($groupe ? ' — '.$groupe->nom : '') }}</h3>
            <p class="text-xs text-slate-500 mt-0.5">Semaine {{ $weekNumber }}</p>
        </div>

        <div class="flex items-center gap-1">
            <a href="{{ $prevUrl }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50" title="Semaine précédente">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <a href="{{ $todayUrl }}" class="inline-flex h-9 items-center rounded-lg border border-slate-200 px-3 text-sm font-medium text-slate-700 hover:bg-slate-50">Aujourd’hui</a>
            <span class="px-2 text-sm font-medium text-slate-800 whitespace-nowrap">{{ $semaineLabel }}</span>
            <a href="{{ $nextUrl }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50" title="Semaine suivante">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        @unless($enseignantVu)
        <form method="GET" action="{{ route('ecole.dashboard') }}" class="flex items-center gap-2">
            <input type="hidden" name="semaine" value="{{ $monday->toDateString() }}">
            <label for="groupe-emploi" class="text-xs font-semibold uppercase tracking-wider text-slate-500">Groupe</label>
            <select id="groupe-emploi" name="groupe" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-escm-primary focus:ring-escm-primary" @disabled($groupes->isEmpty())>
                @forelse($groupes as $ligne)
                    <option value="{{ $ligne->id }}" @selected($groupe && (int) $groupe->id === (int) $ligne->id)>{{ $ligne->nom }}</option>
                @empty
                    <option value="">Aucun groupe</option>
                @endforelse
            </select>
        </form>
        @endunless
    </div>

    @include('ecole.partials.emploi-grille')

    <div class="px-5 py-3 border-t border-slate-100 text-center">
        @if($enseignantVu)
            <a href="{{ $lienEmploi }}" class="text-sm font-medium text-escm-primary hover:text-escm-primary-dark">Voir l’emploi du temps de {{ $enseignantVu->nom_complet }}</a>
        @elseif($groupe)
            <a href="{{ $lienEmploi ?? route('ecole.emplois.index', ['groupe' => $groupe->id]) }}" class="text-sm font-medium text-escm-primary hover:text-escm-primary-dark">{{ auth()->user()?->isParent() || \App\Support\VueCompte::current() ? 'Voir l’emploi du temps de '.$groupe->nom : 'Gérer l’emploi du temps de '.$groupe->nom }}</a>
        @elseif(! auth()->user()?->isParent())
            <a href="{{ route('ecole.groupes.index') }}" class="text-sm font-medium text-escm-primary hover:text-escm-primary-dark">Créer un groupe</a>
        @endif
    </div>
</div>
@endsection
