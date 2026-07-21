@php
    $pickerAnnee = (int) ($annee ?? request('annee', now()->year));
    $pickerMois = (int) ($mois ?? request('mois', now()->month));
    if ($pickerMois < 1 || $pickerMois > 12) {
        $pickerMois = (int) now()->month;
    }
    $pickerLabel = $moisLabel ?? \Carbon\Carbon::create($pickerAnnee, $pickerMois, 1)->locale('fr')->isoFormat('MMMM YYYY');
    $moisNoms = [
        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
        5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
        9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
    ];
@endphp

<header class="sticky top-0 z-30 bg-slate-100/80 backdrop-blur-sm border-b border-slate-200">
    <div class="flex items-center justify-between px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center gap-4">
            <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900">{{ $title ?? 'Dashboard' }}</h1>
                @isset($subtitle)
                    <p class="text-sm text-slate-500 mt-0.5">{{ $subtitle }}</p>
                @endisset
            </div>
        </div>

        <div class="flex items-center gap-3 sm:gap-5">
            {{-- Sélecteur mois / année --}}
            <div
                class="relative"
                x-data="{
                    open: false,
                    annee: {{ $pickerAnnee }},
                    go(m) {
                        window.location.href = @js(route('dashboard')) + '?annee=' + this.annee + '&mois=' + m;
                    }
                }"
                @keydown.escape.window="open = false"
            >
                <button
                    type="button"
                    @click="open = !open"
                    class="flex items-center gap-2 bg-white border border-slate-200 rounded-lg px-2.5 sm:px-3 py-2 text-sm text-slate-700 shadow-sm hover:border-slate-300 hover:bg-slate-50 transition-colors"
                >
                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span class="font-medium capitalize max-w-[7.5rem] sm:max-w-none truncate">{{ $pickerLabel }}</span>
                    <svg class="w-4 h-4 text-slate-400 transition-transform shrink-0" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>

                <div
                    x-show="open"
                    x-cloak
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    @click.outside="open = false"
                    class="absolute right-0 mt-2 w-72 bg-white rounded-xl border border-slate-200 shadow-lg p-3 z-50 origin-top-right"
                >
                    <div class="flex items-center justify-between mb-3 px-1">
                        <button type="button" @click="annee--" class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100" title="Année précédente">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <span class="text-sm font-semibold text-slate-900" x-text="annee"></span>
                        <button type="button" @click="annee++" class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100" title="Année suivante">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                    <div class="grid grid-cols-3 gap-1.5">
                        @foreach($moisNoms as $num => $nom)
                            <button
                                type="button"
                                @click="go({{ $num }})"
                                class="px-2 py-2 rounded-lg text-xs font-medium transition-colors"
                                :class="annee === {{ $pickerAnnee }} && {{ $num }} === {{ $pickerMois }}
                                    ? 'bg-escm-primary text-white'
                                    : 'text-slate-700 hover:bg-slate-100'"
                            >{{ $nom }}</button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Notifications --}}
            <a href="{{ route('dashboard', ['annee' => $pickerAnnee, 'mois' => $pickerMois]) }}#alertes" class="relative p-2 rounded-lg text-slate-500 hover:bg-white hover:text-slate-700 transition-colors" title="Alertes">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                @if(($nbAlertes ?? 0) > 0)
                    <span class="absolute top-1 right-1 flex h-4 min-w-[1rem] px-0.5 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white">{{ $nbAlertes > 9 ? '9+' : $nbAlertes }}</span>
                @endif
            </a>

            {{-- User profile --}}
            @include('layouts.partials.header-user')
        </div>
    </div>
</header>
