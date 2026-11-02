@auth
@if(auth()->user()->canAccess('crm.view'))
<div
    x-data="crmTaskBubble({ pollUrl: @js(route('crm.tasks.due')) })"
    class="contents"
>
    <div
        x-show="open && count > 0"
        x-cloak
        x-transition
        class="fixed z-[80] w-[min(100vw-2rem,20rem)] rounded-2xl border border-slate-200 bg-white shadow-2xl overflow-hidden"
        :style="panelStyle()"
        @click.outside="open = false"
    >
        <div class="flex items-center justify-between gap-2 px-4 py-3 bg-escm-primary text-white">
            <div class="flex items-center gap-2 min-w-0">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-white/15">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-semibold truncate">Tâches échues</p>
                    <p class="text-[11px] text-white/80"><span x-text="count"></span> à traiter</p>
                </div>
            </div>
            <button type="button" @click="open = false" class="p-1 rounded-lg hover:bg-white/15" aria-label="Fermer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <ul class="max-h-64 overflow-y-auto divide-y divide-slate-100">
            <template x-for="task in tasks" :key="task.id">
                <li>
                    <a :href="task.url" class="block px-4 py-3 hover:bg-slate-50 transition-colors">
                        <p class="text-sm font-medium text-slate-900 truncate" x-text="task.candidate"></p>
                        <p class="text-xs text-slate-600 mt-0.5 truncate" x-text="task.type"></p>
                        <p class="text-[11px] text-red-600 mt-1 font-medium" x-text="'Échéance ' + task.due_at + ' · ' + task.due_human"></p>
                    </a>
                </li>
            </template>
        </ul>
    </div>

    <div
        x-ref="bubble"
        x-show="count > 0"
        x-cloak
        x-transition.opacity
        class="fixed z-[85] select-none touch-none"
        :style="style()"
        @pointerdown="onPointerDown($event)"
        @pointermove="onPointerMove($event)"
        @pointerup="onPointerUp()"
        @pointercancel="onPointerUp()"
    >
        <button
            type="button"
            @click="toggle()"
            class="relative flex h-14 w-14 items-center justify-center rounded-full bg-escm-primary text-white shadow-xl ring-4 ring-white/80 hover:bg-escm-primary-dark transition-colors cursor-grab active:cursor-grabbing"
            :class="dragging && 'scale-105'"
            title="Tâches CRM échues — glisser pour déplacer"
            aria-label="Notifications tâches CRM"
        >
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 10-4 0v1.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <span
                class="absolute -top-1 -right-1 min-w-[1.35rem] h-[1.35rem] px-1 rounded-full bg-red-500 text-[11px] font-bold flex items-center justify-center ring-2 ring-white notif-badge-blink"
                x-text="count > 9 ? '9+' : count"
            ></span>
            <span class="absolute inset-0 rounded-full animate-ping bg-red-400/30 pointer-events-none"></span>
        </button>
    </div>
</div>
@endif
@endauth
