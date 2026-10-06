@php
    $title = $enseignant->nom_complet;
    $subtitle = $enseignant->fonction ?: 'CV enseignant';
@endphp

@extends('layouts.app')

@section('content')
<div class="mb-4">
    <a href="{{ route('enseignants.index') }}" class="text-xs font-semibold text-escm-primary hover:underline">← Tous les enseignants</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1">
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="aspect-square bg-slate-200">
                @if($enseignant->photo_url)
                    <img src="{{ $enseignant->photo_url }}" alt="{{ $enseignant->nom_complet }}" class="h-full w-full object-cover">
                @else
                    <div class="flex h-full w-full items-center justify-center bg-escm-primary text-5xl font-bold text-white">{{ $enseignant->initiales }}</div>
                @endif
            </div>
            <div class="p-5 space-y-2">
                <h2 class="text-lg font-semibold text-slate-900">{{ $enseignant->nom_complet }}</h2>
                @if($enseignant->fonction)
                    <p class="text-sm text-slate-600">{{ $enseignant->fonction }}</p>
                @endif
                @if($enseignant->matieres)
                    <p class="text-sm text-slate-500">{{ $enseignant->matieres }}</p>
                @endif
                @if($enseignant->email)
                    <p class="text-sm text-slate-700">{{ $enseignant->email }}</p>
                @endif
                @if($enseignant->telephone)
                    <p class="text-sm text-slate-700">{{ $enseignant->telephone }}</p>
                @endif
                <div class="flex gap-3 pt-2">
                    @if(auth()->user()->canAccess('enseignants.update'))
                        <a href="{{ route('enseignants.edit', $enseignant) }}" class="text-xs font-semibold text-escm-primary hover:underline">Modifier</a>
                    @endif
                    @if(auth()->user()->canAccess('enseignants.delete'))
                        <form method="POST" action="{{ route('enseignants.destroy', $enseignant) }}" onsubmit="return confirm('Supprimer cet enseignant ?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs font-semibold text-red-600 hover:underline">Supprimer</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="lg:col-span-2 space-y-4">
        @foreach(['biographie' => 'Biographie', 'formation' => 'Formation', 'experience' => 'Expérience'] as $field => $label)
            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h3 class="text-sm font-semibold text-slate-900 mb-2">{{ $label }}</h3>
                <p class="whitespace-pre-line text-sm text-slate-700">{{ $enseignant->{$field} ?: '—' }}</p>
            </section>
        @endforeach
    </div>
</div>
@endsection
