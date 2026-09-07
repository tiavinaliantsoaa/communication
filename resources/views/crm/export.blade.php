@php
    $title = 'CRM — Export';
    $subtitle = 'Exporter des candidats vers Excel';
    $allColumnKeys = array_keys($columns);
@endphp

@extends('layouts.app')

@section('content')
<form method="POST" action="{{ route('crm.export.download') }}" class="max-w-4xl space-y-6"
      x-data="{
          columns: @js($filters['columns']),
          all: @js($allColumnKeys),
          defaults: @js($defaultColumns),
          selectDefaults() { this.columns = [...this.defaults]; },
          selectAll() { this.columns = [...this.all]; }
      }">
    @csrf

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <h3 class="text-sm font-semibold text-slate-800 mb-1">Liste à exporter</h3>
        <p class="text-xs text-slate-500 mb-4">Filtrez les candidats par statut de dossier, année d’intake et programme. Laissez un filtre sur « Tous » pour ne pas le restreindre.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Candidats</label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                    @foreach($populations as $key => $label)
                        <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2.5 text-sm cursor-pointer hover:bg-slate-50 has-[:checked]:border-escm-primary has-[:checked]:bg-escm-primary/5">
                            <input type="radio" name="population" value="{{ $key }}" class="text-escm-primary focus:ring-escm-primary"
                                   @checked(old('population', $filters['population']) === $key)>
                            <span class="font-medium text-slate-700">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Programme</label>
                <div class="relative" x-data="{
                    open: false,
                    selected: @js(old('programme', $filters['programmes'])),
                    label() { return this.selected.length ? this.selected.join(', ') : 'Tous'; }
                }" @keydown.escape.window="open = false">
                    <button type="button" @click="open = !open"
                            class="w-full flex items-center justify-between gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-left text-slate-700 focus:border-escm-primary focus:ring-1 focus:ring-escm-primary">
                        <span class="truncate" x-text="label()"></span>
                        <svg class="w-4 h-4 text-slate-400 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-cloak @click.outside="open = false"
                         class="absolute z-30 mt-1 w-full rounded-lg border border-slate-200 bg-white shadow-lg py-1 max-h-56 overflow-y-auto">
                        @forelse($programmes as $programme)
                            <label class="flex items-center gap-2 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 cursor-pointer">
                                <input type="checkbox" name="programme[]" value="{{ $programme }}" x-model="selected"
                                       class="rounded border-slate-300 text-escm-primary focus:ring-escm-primary">
                                <span>{{ $programme }}</span>
                            </label>
                        @empty
                            <p class="px-3 py-2 text-sm text-slate-400">Aucun programme</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Année / Intake</label>
                <div class="relative" x-data="{
                    open: false,
                    selected: @js(old('intake', $filters['intakes'])),
                    label() { return this.selected.length ? this.selected.join(', ') : 'Tous'; }
                }" @keydown.escape.window="open = false">
                    <button type="button" @click="open = !open"
                            class="w-full flex items-center justify-between gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-left text-slate-700 focus:border-escm-primary focus:ring-1 focus:ring-escm-primary">
                        <span class="truncate" x-text="label()"></span>
                        <svg class="w-4 h-4 text-slate-400 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-cloak @click.outside="open = false"
                         class="absolute z-30 mt-1 w-full rounded-lg border border-slate-200 bg-white shadow-lg py-1 max-h-56 overflow-y-auto">
                        @forelse($intakes as $intake)
                            <label class="flex items-center gap-2 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 cursor-pointer">
                                <input type="checkbox" name="intake[]" value="{{ $intake }}" x-model="selected"
                                       class="rounded border-slate-300 text-escm-primary focus:ring-escm-primary">
                                <span>{{ $intake }}</span>
                            </label>
                        @empty
                            <p class="px-3 py-2 text-sm text-slate-400">Aucune rentrée</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Statut pipeline</label>
                <select name="statut" class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
                    <option value="">Tous</option>
                    @foreach($statuts as $key => $label)
                        <option value="{{ $key }}" @selected(old('statut', $filters['statut']) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Conseiller</label>
                <select name="advisor_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
                    <option value="">Tous</option>
                    @foreach($advisors as $advisor)
                        <option value="{{ $advisor->id }}" @selected((string) old('advisor_id', $filters['advisor_id']) === (string) $advisor->id)>{{ $advisor->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-4">
            <div>
                <h3 class="text-sm font-semibold text-slate-800">Colonnes du fichier Excel</h3>
                <p class="text-xs text-slate-500 mt-0.5">Cochez uniquement les informations visibles dans le fichier. Par défaut : nom et téléphone.</p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button type="button" @click="selectDefaults()" class="text-xs font-medium text-slate-600 hover:text-slate-900">Nom + téléphone</button>
                <span class="text-slate-300">·</span>
                <button type="button" @click="selectAll()" class="text-xs font-medium text-escm-primary hover:underline">Tout sélectionner</button>
            </div>
        </div>
        @error('columns')<p class="mb-3 text-xs text-red-600">{{ $message }}</p>@enderror

        <div class="space-y-5">
            @foreach($columnGroups as $groupLabel => $keys)
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-2">{{ $groupLabel }}</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach($keys as $key)
                            <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm cursor-pointer hover:bg-slate-50 has-[:checked]:border-escm-primary has-[:checked]:bg-escm-primary/5">
                                <input type="checkbox" name="columns[]" value="{{ $key }}" x-model="columns"
                                       class="rounded border-slate-300 text-escm-primary focus:ring-escm-primary">
                                <span class="text-slate-700">{{ $columns[$key] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <p class="text-sm text-slate-600">
            <span class="font-semibold text-slate-900">{{ number_format($count, 0, ',', ' ') }}</span>
            candidat{{ $count > 1 ? 's' : '' }} correspondent actuellement à ces filtres.
            <button type="submit" formmethod="GET" formaction="{{ route('crm.export') }}" class="font-medium text-escm-primary hover:underline">Actualiser le nombre</button>
        </p>
        <button type="submit" class="inline-flex items-center justify-center gap-2 bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-5 py-2.5 rounded-lg">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Exporter en Excel
        </button>
    </div>
</form>
@endsection
