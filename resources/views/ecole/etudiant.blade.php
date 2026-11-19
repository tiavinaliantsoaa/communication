@php
    $valeur = function (?string $texte): string {
        $texte = trim((string) $texte);

        return $texte !== '' ? $texte : '—';
    };
    $lignes = [
        ['Nom', $etudiant->nom_complet, false],
        ['Nom complet', $etudiant->nomCompletOfficiel(), false],
        ['Nom en caractères', $etudiant->nom_caracteres, false],
        ['Palier', $etudiant->palierAffiche(), false],
        ['Groupe-repère', $groupe?->nom, false],
        ['Nom d’utilisateur', $etudiant->nom_utilisateur, false],
        ['Âge', $etudiant->ageLibelle(), false],
        ['Site web', $etudiant->site_web, 'site'],
        ['Courriel', $etudiant->courriel(), 'mail'],
        ['Historique de l’école', $etudiant->historiqueEcole(), false],
        ['Code de verrouillage', $etudiant->code_verrouillage, false],
        ['Matricule de l’élève', $etudiant->matricule, false],
        ['Domicile', $etudiant->domicileAffiche(), false],
    ];
@endphp

@extends('layouts.app')

@section('content')
<div class="mb-4">
    @if(auth()->user()?->isParent())
        <a href="{{ route('ecole.dashboard') }}" class="text-sm font-medium text-escm-primary hover:text-escm-primary-dark">Retour au dashboard</a>
    @else
        <a href="{{ $groupe ? route('ecole.groupes.show', $groupe) : route('ecole.groupes.index') }}" class="text-sm font-medium text-escm-primary hover:text-escm-primary-dark">Retour au groupe</a>
    @endif
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="mx-auto flex h-20 w-16 items-end justify-center overflow-hidden rounded border border-slate-200 bg-slate-200">
            <svg viewBox="0 0 64 80" class="h-full w-full text-white" fill="currentColor" aria-hidden="true">
                <circle cx="32" cy="22" r="12"/>
                <path d="M8 80c2-20 12-30 24-30s22 10 24 30"/>
            </svg>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm lg:col-span-2">
        <div class="flex flex-wrap items-start justify-between gap-3 px-5 py-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Aperçu</p>
                <h2 class="mt-1 text-sm font-semibold text-slate-900">Information générale</h2>
            </div>
            @if($peutModifier)
                <a href="{{ route('ecole.etudiants.edit', $etudiant) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                    </svg>
                    Modifier
                </a>
            @endif
        </div>
        <div class="grid grid-cols-1 border-t border-slate-100 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($lignes as [$label, $texte, $type])
                <div class="border-b border-slate-100 px-5 py-3">
                    <p class="text-[11px] font-semibold text-slate-500">{{ $label }}</p>
                    @if($type === 'mail' && $valeur($texte) !== '—')
                        <a href="mailto:{{ $texte }}" class="mt-0.5 block break-all text-sm font-medium text-red-600">{{ $texte }}</a>
                    @elseif($type === 'site' && preg_match('#^https?://#i', trim((string) $texte)))
                        <a href="{{ $texte }}" class="mt-0.5 block break-all text-sm text-escm-primary hover:text-escm-primary-dark" target="_blank" rel="noopener">{{ $texte }}</a>
                    @else
                        <p class="mt-0.5 text-sm text-slate-900">{{ $valeur($texte) }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="mt-6 rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
        <div>
            <h3 class="text-sm font-semibold text-slate-900">Emploi du temps</h3>
            <p class="mt-0.5 text-xs text-slate-500">Semaine {{ $weekNumber }}{{ $groupe ? ' — '.$groupe->nom : '' }}</p>
        </div>
        <div class="flex items-center gap-1">
            <a href="{{ $prevUrl }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50" title="Semaine précédente">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <a href="{{ $todayUrl }}" class="inline-flex h-9 items-center rounded-lg border border-slate-200 px-3 text-sm font-medium text-slate-700 hover:bg-slate-50">Aujourd’hui</a>
            <span class="whitespace-nowrap px-2 text-sm font-medium text-slate-800">{{ $semaineLabel }}</span>
            <a href="{{ $nextUrl }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50" title="Semaine suivante">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    </div>
    @include('ecole.partials.emploi-grille')
</div>
@endsection
