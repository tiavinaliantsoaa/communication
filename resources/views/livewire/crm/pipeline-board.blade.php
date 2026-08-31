<div>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="relative w-full max-w-md">
            <label for="pipeline-search" class="sr-only">Rechercher un candidat par nom ou téléphone</label>
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 100-15 7.5 7.5 0 000 15z"/>
            </svg>
            <input id="pipeline-search"
                   type="search"
                   wire:model.live.debounce.300ms="search"
                   placeholder="Rechercher par nom ou téléphone…"
                   autocomplete="off"
                   class="w-full rounded-lg border-slate-300 py-2 pl-9 pr-9 text-sm focus:border-escm-primary focus:ring-escm-primary">
            @if($search !== '')
                <button type="button"
                        wire:click="$set('search', '')"
                        class="absolute right-2 top-1/2 -translate-y-1/2 rounded p-1 text-slate-400 hover:text-slate-700"
                        title="Effacer la recherche">
                    <span class="sr-only">Effacer</span>
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            @endif
        </div>
        @if(trim($search) !== '')
            <p class="text-sm text-slate-500 whitespace-nowrap">
                {{ $matchCount }} résultat{{ $matchCount > 1 ? 's' : '' }}
            </p>
        @endif
    </div>

    <div class="mb-4">
        <p class="text-sm text-slate-500">Les colonnes se mettent à jour automatiquement selon les informations du candidat (programme et cases d’avancement). Le glisser-déposer est désactivé. Les candidatures abandonnées n’apparaissent pas ici.</p>
    </div>

    <div class="flex gap-4 overflow-x-auto pb-4 -mx-1 px-1 snap-x" id="crm-pipeline-board">
        @foreach($columns as $column)
            <div class="snap-start shrink-0 w-72 flex flex-col bg-slate-50/80 rounded-xl border border-slate-200 max-h-[calc(100vh-14rem)]"
                 wire:key="col-{{ $column['key'] }}">
                <div class="px-3 py-3 border-b border-slate-200/80 sticky top-0 bg-slate-50/95 backdrop-blur rounded-t-xl z-10">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-600">{{ $column['label'] }}</h3>
                        <span class="inline-flex items-center justify-center min-w-[1.5rem] h-5 px-1.5 rounded-full bg-white border border-slate-200 text-[11px] font-semibold text-slate-600">
                            {{ $column['candidates']->count() }}
                        </span>
                    </div>
                    @if(!empty($column['condition']))
                        <p class="mt-1 text-[10px] text-slate-400 leading-snug">{{ $column['condition'] }}</p>
                    @endif
                </div>

                <div class="flex-1 overflow-y-auto p-2 space-y-2 min-h-[120px]">
                    @forelse($column['candidates'] as $candidate)
                        <a href="{{ route('crm.candidats.show', $candidate) }}"
                           wire:key="card-{{ $candidate->id }}"
                           class="block bg-white rounded-lg border border-slate-200 shadow-sm p-3 hover:border-escm-primary/40 hover:shadow transition-all">
                            <p class="text-sm font-semibold text-slate-900 truncate">{{ $candidate->full_name }}</p>
                            <p class="mt-1 text-xs text-slate-500 truncate">{{ $candidate->programme ?: 'Sans programme' }}</p>
                            <div class="mt-2 flex items-center justify-between gap-2 text-[11px] text-slate-500">
                                <span class="truncate">{{ $candidate->telephone ?: '—' }}</span>
                                <span class="truncate text-right">{{ $candidate->advisor?->name ?: '—' }}</span>
                            </div>
                        </a>
                    @empty
                        <div class="px-2 py-6 text-center text-xs text-slate-400 select-none">Aucun candidat</div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</div>
