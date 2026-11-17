@php
    $total = $etudiants->total();
    $resultats = $total === 0
        ? 'Résultats 0 de 0'
        : 'Résultats '.$etudiants->firstItem().' to '.$etudiants->lastItem().' de '.$total;
    $bouton = 'inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50';
@endphp

@extends('layouts.app')

@section('content')
<div class="rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs text-slate-500">{{ $resultats }}</p>
        <div class="flex flex-wrap items-center gap-2">
            <form method="POST" action="{{ route('ecole.groupes.presence', $groupe) }}" onsubmit="return confirm('Enregistrer la présence de tous les étudiants de ce groupe pour aujourd’hui ?')">
                @csrf
                <button type="submit" class="{{ $bouton }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                    </svg>
                    Enregistrer une présence
                </button>
            </form>
            <a href="{{ route('ecole.groupes.export', $groupe) }}" class="{{ $bouton }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                </svg>
                Exporter vers Excel
            </a>
        </div>
    </div>

    <div class="border-t border-slate-200 bg-sky-50 px-4 py-8 sm:px-8">
        @if($etudiants->isEmpty())
            <p class="py-10 text-center text-sm text-slate-500">Aucun étudiant dans ce groupe.</p>
        @else
            <div class="grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                @foreach($etudiants as $etudiant)
                    <div>
                        <div class="mx-auto flex h-20 w-16 items-end justify-center overflow-hidden rounded border border-slate-200 bg-slate-200">
                            <svg viewBox="0 0 64 80" class="h-full w-full text-white" fill="currentColor" aria-hidden="true">
                                <circle cx="32" cy="22" r="12"/>
                                <path d="M8 80c2-20 12-30 24-30s22 10 24 30"/>
                            </svg>
                        </div>
                        <p class="mt-2 text-center text-xs font-semibold leading-4 text-red-600">{{ $etudiant->nom_complet }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @if($etudiants->hasPages())
        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-4 py-3">
            @if($etudiants->onFirstPage())
                <span class="inline-flex h-9 items-center rounded-lg border border-slate-200 px-3 text-sm text-slate-400">Précédent</span>
            @else
                <a href="{{ $etudiants->previousPageUrl() }}" class="inline-flex h-9 items-center rounded-lg border border-slate-200 px-3 text-sm font-medium text-slate-700 hover:bg-slate-50">Précédent</a>
            @endif
            @if($etudiants->hasMorePages())
                <a href="{{ $etudiants->nextPageUrl() }}" class="inline-flex h-9 items-center rounded-lg border border-slate-200 px-3 text-sm font-medium text-slate-700 hover:bg-slate-50">Suivant</a>
            @else
                <span class="inline-flex h-9 items-center rounded-lg border border-slate-200 px-3 text-sm text-slate-400">Suivant</span>
            @endif
        </div>
    @endif
</div>
@endsection
