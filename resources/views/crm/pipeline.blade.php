@php
    $title = 'CRM — Pipeline';
    $subtitle = 'Statuts automatiques selon l’avancement du candidat';
@endphp

@extends('layouts.app')

@section('content')
<div x-data="crmPipeline(@js($columns))">
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="relative w-full max-w-md">
            <label for="pipeline-search" class="sr-only">Rechercher un candidat par nom ou téléphone</label>
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 100-15 7.5 7.5 0 000 15z"/>
            </svg>
            <input id="pipeline-search"
                   type="search"
                   x-model="search"
                   placeholder="Rechercher par nom ou téléphone…"
                   autocomplete="off"
                   class="w-full rounded-lg border-slate-300 py-2 pl-9 pr-9 text-sm focus:border-escm-primary focus:ring-escm-primary">
            <button type="button"
                    x-show="search"
                    x-cloak
                    @click="search = ''"
                    class="absolute right-2 top-1/2 -translate-y-1/2 rounded p-1 text-slate-400 hover:text-slate-700"
                    title="Effacer la recherche">
                <span class="sr-only">Effacer</span>
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <p class="text-sm text-slate-500 whitespace-nowrap" x-show="search.trim() !== ''" x-cloak>
            <span x-text="matchCount"></span> <span x-text="matchCount > 1 ? 'résultats' : 'résultat'"></span>
        </p>
    </div>

    <div class="mb-4">
        <p class="text-sm text-slate-500">Les colonnes se mettent à jour automatiquement selon les informations du candidat (programme et cases d’avancement). Le glisser-déposer est désactivé. Les candidatures abandonnées n’apparaissent pas ici.</p>
    </div>

    <div class="flex gap-4 overflow-x-auto pb-4 -mx-1 px-1 snap-x" id="crm-pipeline-board">
        <template x-for="column in columns" :key="column.key">
            <div class="snap-start shrink-0 w-72 flex flex-col bg-slate-50/80 rounded-xl border border-slate-200 max-h-[calc(100vh-14rem)]">
                <div class="px-3 py-3 border-b border-slate-200/80 sticky top-0 bg-slate-50/95 backdrop-blur rounded-t-xl z-10">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-600" x-text="column.label"></h3>
                        <span class="inline-flex items-center justify-center min-w-[1.5rem] h-5 px-1.5 rounded-full bg-white border border-slate-200 text-[11px] font-semibold text-slate-600"
                              x-text="visibleCandidates(column).length"></span>
                    </div>
                    <p class="mt-1 text-[10px] text-slate-400 leading-snug" x-show="column.condition" x-text="column.condition"></p>
                </div>

                <div class="flex-1 overflow-y-auto p-2 space-y-2 min-h-[120px]">
                    <template x-for="candidate in visibleCandidates(column)" :key="candidate.id">
                        <a :href="candidate.url"
                           class="block bg-white rounded-lg border border-slate-200 shadow-sm p-3 hover:border-escm-primary/40 hover:shadow transition-all">
                            <p class="text-sm font-semibold text-slate-900 truncate" x-text="candidate.nom"></p>
                            <p class="mt-1 text-xs text-slate-500 truncate" x-text="candidate.programme || 'Sans programme'"></p>
                            <div class="mt-2 flex items-center justify-between gap-2 text-[11px] text-slate-500">
                                <span class="truncate" x-text="candidate.telephone || '—'"></span>
                                <span class="truncate text-right" x-text="candidate.advisor || '—'"></span>
                            </div>
                        </a>
                    </template>
                    <div class="px-2 py-6 text-center text-xs text-slate-400 select-none"
                         x-show="visibleCandidates(column).length === 0"
                         x-text="search.trim() !== '' ? 'Aucun résultat' : 'Aucun candidat'"></div>
                </div>
            </div>
        </template>
    </div>
</div>
@endsection

@push('scripts')
<script>
function crmPipeline(columns) {
    return {
        columns: columns,
        search: '',

        normalize(value) {
            return String(value ?? '').toLowerCase().trim();
        },

        digits(value) {
            return String(value ?? '').replace(/\D+/g, '');
        },

        matches(candidate) {
            const term = this.search.trim();
            if (term === '') return true;

            const haystack = this.normalize(candidate.nom);
            if (haystack.includes(this.normalize(term))) return true;

            const termDigits = this.digits(term);
            if (termDigits !== '' && this.digits(candidate.telephone).includes(termDigits)) return true;

            return false;
        },

        visibleCandidates(column) {
            if (this.search.trim() === '') return column.candidates;
            return column.candidates.filter((c) => this.matches(c));
        },

        get matchCount() {
            return this.columns.reduce((total, column) => total + this.visibleCandidates(column).length, 0);
        },
    };
}
</script>
@endpush
