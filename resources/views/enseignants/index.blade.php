@php
    $title = 'CV enseignant';
    $subtitle = 'Liste des enseignants';
@endphp

@extends('layouts.app')

@section('content')
@if(auth()->user()->canAccess('enseignants.create'))
    <x-page-actions :create-route="route('enseignants.create')" create-label="Nouvel enseignant" />
@endif

@if($enseignants->isEmpty())
    <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center text-sm text-slate-500">
        Aucun enseignant pour le moment.
    </div>
@else
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
        @foreach($enseignants as $enseignant)
            <a href="{{ route('enseignants.show', $enseignant) }}" class="group block">
                <div class="aspect-square overflow-hidden rounded-xl bg-slate-200 ring-1 ring-slate-200 shadow-sm">
                    @if($enseignant->photo_url)
                        <img src="{{ $enseignant->photo_url }}" alt="{{ $enseignant->nom_complet }}" class="h-full w-full object-cover transition group-hover:scale-105">
                    @else
                        <div class="flex h-full w-full items-center justify-center bg-escm-primary text-3xl font-bold text-white">
                            {{ $enseignant->initiales }}
                        </div>
                    @endif
                </div>
                <p class="mt-2 truncate text-sm font-semibold text-slate-900 group-hover:text-escm-primary">{{ $enseignant->nom_complet }}</p>
                <p class="truncate text-xs text-slate-500">{{ $enseignant->fonction ?: 'Enseignant' }}</p>
            </a>
        @endforeach
    </div>
@endif
@endsection
