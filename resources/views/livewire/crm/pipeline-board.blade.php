<div>
    <div class="mb-4 flex items-center justify-between gap-3">
        <p class="text-sm text-slate-500">Glissez-déposez les cartes entre les colonnes pour changer le statut.</p>
        <div wire:loading.flex wire:target="moveCandidate" class="items-center gap-2 text-xs text-escm-primary font-medium">
            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
            Enregistrement…
        </div>
    </div>

    <div class="flex gap-4 overflow-x-auto pb-4 -mx-1 px-1 snap-x" id="crm-pipeline-board">
        @foreach($columns as $column)
            <div class="snap-start shrink-0 w-72 flex flex-col bg-slate-50/80 rounded-xl border border-slate-200 max-h-[calc(100vh-14rem)]"
                 wire:key="col-{{ $column['key'] }}">
                <div class="px-3 py-3 border-b border-slate-200/80 flex items-center justify-between sticky top-0 bg-slate-50/95 backdrop-blur rounded-t-xl z-10">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-600">{{ $column['label'] }}</h3>
                    <span class="inline-flex items-center justify-center min-w-[1.5rem] h-5 px-1.5 rounded-full bg-white border border-slate-200 text-[11px] font-semibold text-slate-600">
                        {{ $column['candidates']->count() }}
                    </span>
                </div>

                <div
                    class="flex-1 overflow-y-auto p-2 space-y-2 min-h-[120px] crm-pipeline-column"
                    data-status="{{ $column['key'] }}"
                    data-can-edit="{{ auth()->user()->canAccess('crm.update') ? '1' : '0' }}"
                >
                    @foreach($column['candidates'] as $candidate)
                        <div
                            wire:key="card-{{ $candidate->id }}"
                            data-candidate-id="{{ $candidate->id }}"
                            class="crm-card bg-white rounded-lg border border-slate-200 shadow-sm p-3 hover:border-escm-primary/40 hover:shadow transition-all {{ auth()->user()->canAccess('crm.update') ? 'cursor-grab active:cursor-grabbing' : '' }}"
                        >
                            <a href="{{ route('crm.candidats.show', $candidate) }}" class="block" onclick="event.stopPropagation()">
                                <p class="text-sm font-semibold text-slate-900 truncate">{{ $candidate->full_name }}</p>
                                <p class="mt-1 text-xs text-slate-500 truncate">{{ $candidate->programme ?: 'Sans programme' }}</p>
                                <div class="mt-2 flex items-center justify-between gap-2 text-[11px] text-slate-500">
                                    <span class="truncate">{{ $candidate->telephone ?: '—' }}</span>
                                    <span class="truncate text-right">{{ $candidate->advisor?->name ?: '—' }}</span>
                                </div>
                            </a>
                        </div>
                    @endforeach

                    @if($column['candidates']->isEmpty())
                        <div class="px-2 py-6 text-center text-xs text-slate-400 pointer-events-none select-none">Aucun candidat</div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>

@assets
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
@endassets

@script
<script>
    const initCrmSortables = () => {
        if (typeof Sortable === 'undefined') return;

        document.querySelectorAll('.crm-pipeline-column').forEach((el) => {
            if (el.dataset.canEdit !== '1') return;
            if (el._sortable) {
                el._sortable.destroy();
            }
            el._sortable = Sortable.create(el, {
                group: 'crm-pipeline',
                animation: 180,
                draggable: '.crm-card',
                ghostClass: 'opacity-40',
                dragClass: 'shadow-lg',
                filter: 'a',
                preventOnFilter: false,
                onEnd: (evt) => {
                    const to = evt.to;
                    const ids = [...to.querySelectorAll('[data-candidate-id]')].map((node) => Number(node.dataset.candidateId));
                    const candidateId = Number(evt.item.dataset.candidateId);
                    const status = to.dataset.status;
                    $wire.moveCandidate(candidateId, status, ids);
                },
            });
        });
    };

    initCrmSortables();

    $wire.on('pipeline-updated', () => {
        queueMicrotask(initCrmSortables);
    });

    Livewire.hook('morphed', () => {
        queueMicrotask(initCrmSortables);
    });
</script>
@endscript
