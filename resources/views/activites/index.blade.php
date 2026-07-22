@php
    $title = $title ?? 'Activité';
    $subtitle = $subtitle ?? '';
@endphp

@extends('layouts.app')

@section('content')
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-sm font-semibold text-slate-900">Toutes les activités</h2>
            <p class="text-xs text-slate-500 mt-0.5">Actions des utilisateurs dans l’ensemble de l’outil</p>
        </div>
        <form method="GET" class="flex items-center gap-2">
            <select name="module" onchange="this.form.submit()" class="rounded-lg border-slate-200 text-xs focus:border-escm-primary focus:ring-escm-primary">
                <option value="">Tous les modules</option>
                @foreach($modules as $key => $label)
                    <option value="{{ $key }}" @selected(($moduleActif ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="divide-y divide-slate-100">
        @forelse($activites as $activite)
            <div class="px-5 py-3.5 flex gap-3 hover:bg-slate-50/60">
                <div class="mt-0.5">
                    @if($activite->user)
                        <x-user-avatar :user="$activite->user" size="sm" />
                    @else
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-200 text-[10px] font-bold text-slate-600">?</span>
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2 mb-0.5">
                        <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-semibold {{ $activite->module_color }}">
                            {{ $activite->module_label }}
                        </span>
                        @if($activite->url)
                            <a href="{{ $activite->url }}" class="text-[11px] font-medium text-escm-primary hover:underline">Voir</a>
                        @endif
                    </div>
                    <p class="text-sm text-slate-800">{{ $activite->description }}</p>
                    <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-500">
                        <span>{{ $activite->created_at?->locale('fr')->isoFormat('D MMM YYYY, HH:mm') }}</span>
                        @if($activite->user)
                            <span>
                                par <span class="font-medium text-slate-700">{{ $activite->user->name }}</span>
                                <span class="text-slate-400">({{ '@'.$activite->user->username }})</span>
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="px-5 py-12 text-center">
                <p class="text-sm font-medium text-slate-700">Aucune activité pour le moment</p>
                <p class="text-xs text-slate-500 mt-1">Les actions sur l’outil apparaîtront ici.</p>
            </div>
        @endforelse
    </div>

    @if($activites->hasPages())
        <div class="px-5 py-3 border-t border-slate-100">
            {{ $activites->links() }}
        </div>
    @endif
</div>
@endsection
