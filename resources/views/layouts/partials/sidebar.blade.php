<aside
    class="fixed inset-y-0 left-0 z-50 w-64 bg-escm-sidebar flex flex-col transform transition-transform duration-200 lg:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
>
    {{-- Logo --}}
    <div class="flex items-center gap-3 px-5 py-5 border-b border-slate-700/50">
        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 ring-2 ring-white/20">
            <svg class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"/>
            </svg>
        </div>
        <div>
            <div class="text-white font-bold text-lg leading-tight tracking-wide">ESCM</div>
            <div class="text-slate-400 text-[10px] uppercase tracking-widest">Communication</div>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-6">
        @php
            $isActive = fn($patterns) => collect((array)$patterns)->contains(fn($p) => request()->routeIs($p));
            $can = fn(string $perm) => auth()->user()?->canAccess($perm);
        @endphp

        <div class="space-y-0.5">
            @if($can('dashboard.view'))
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ $isActive('dashboard') ? 'bg-escm-primary text-white' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Dashboard
            </a>
            @endif
            @if($can('statistiques.view'))
            <a href="{{ route('statistiques') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ $isActive('statistiques') ? 'bg-escm-primary text-white' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Statistiques
            </a>
            @endif
            @if($can('activite.view'))
            <a href="{{ route('activite.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ $isActive('activite.*') ? 'bg-escm-primary text-white' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Activité
            </a>
            @endif
        </div>

        @php
            $budgetItems = array_filter([
                $can('budget_annuel.view') ? ['route' => 'budget-annuels.index', 'label' => 'Budget annuel', 'icon' => 'M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z'] : null,
                $can('budget_mensuel.view') ? ['route' => 'budgets.index', 'label' => 'Budget mensuel', 'icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z'] : null,
                $can('depenses.view') ? ['route' => 'depenses.index', 'label' => 'Dépenses', 'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'] : null,
                $can('fournisseurs.view') ? ['route' => 'fournisseurs.index', 'label' => 'Fournisseurs', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'] : null,
                $can('gestion_projet.view') ? ['route' => 'gestion-projet.index', 'label' => 'Gestion de projet', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', 'active' => 'gestion-projet.*'] : null,
            ]);
        @endphp
        @if(count($budgetItems))
        <div>
            <p class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Budget & Dépenses</p>
            <div class="space-y-0.5">
                @foreach($budgetItems as $item)
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors {{ $isActive($item['active'] ?? $item['route']) ? 'bg-escm-primary text-white' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $item['icon'] }}"/></svg>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
        @endif

        @php
            $campagneItems = array_filter([
                $can('campagnes.view') ? ['route' => 'campagnes.index', 'label' => 'Campagnes (Boost FB)', 'icon' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z'] : null,
                $can('calendrier_editorial.view') ? ['route' => 'calendrier-editorial', 'label' => 'Calendrier éditorial', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'] : null,
                $can('evenements.view') ? ['route' => 'evenements.index', 'label' => 'Événements', 'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'] : null,
            ]);
        @endphp
        @if(count($campagneItems))
        <div>
            <p class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Campagnes</p>
            <div class="space-y-0.5">
                @foreach($campagneItems as $item)
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors {{ $isActive($item['route']) ? 'bg-escm-primary text-white' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $item['icon'] }}"/></svg>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
        @endif

        @if($can('suivi_liens.view'))
        <div>
            <p class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Suivi de lien</p>
            <div class="space-y-0.5">
                <a href="{{ route('suivi-liens.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors {{ $isActive('suivi-liens.*') ? 'bg-escm-primary text-white' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                    Suivi de lien
                </a>
            </div>
        </div>
        @endif

        @php
            $stockItems = array_filter([
                $can('stocks.view') ? ['route' => 'stocks.index', 'label' => 'Stocks', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'] : null,
                $can('stocks_mouvements.view') ? ['route' => 'stocks.mouvements.index', 'label' => 'Entrées / Sorties', 'icon' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4'] : null,
            ]);
        @endphp
        @if(count($stockItems))
        <div>
            <p class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Stock Marketing</p>
            <div class="space-y-0.5">
                @foreach($stockItems as $item)
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors {{ $isActive($item['route']) ? 'bg-escm-primary text-white' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $item['icon'] }}"/></svg>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
        @endif
    </nav>

    {{-- Paramètres footer --}}
    <div class="border-t border-slate-700/50 px-3 py-3" x-data="{ settingsOpen: false }">
        <button @click="settingsOpen = !settingsOpen" class="flex w-full items-center justify-between px-3 py-2 rounded-lg text-sm text-slate-300 hover:bg-slate-700/50 hover:text-white transition-colors">
            <span class="flex items-center gap-3">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Paramètres
            </span>
            <svg class="w-4 h-4 transition-transform" :class="settingsOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="settingsOpen" class="mt-1 space-y-0.5 pl-2">
            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-3 py-1.5 rounded text-xs text-slate-400 hover:text-white hover:bg-slate-700/50">Gestion du profil</a>
            @if($can('users.view'))
            <a href="{{ route('users.index') }}" class="flex items-center gap-2 px-3 py-1.5 rounded text-xs text-slate-400 hover:text-white hover:bg-slate-700/50">Gestion des utilisateurs</a>
            @endif
            @if(auth()->user()?->isSuperAdmin())
            <a href="{{ route('acces.index') }}" class="flex items-center gap-2 px-3 py-1.5 rounded text-xs text-slate-400 hover:text-white hover:bg-slate-700/50">Gestion d’accès</a>
            @endif
            @if($can('parametres.systeme'))
            <a href="{{ route('parametres.systeme') }}" class="flex items-center gap-2 px-3 py-1.5 rounded text-xs text-slate-400 hover:text-white hover:bg-slate-700/50">Configuration système</a>
            @endif
        </div>
    </div>
</aside>
