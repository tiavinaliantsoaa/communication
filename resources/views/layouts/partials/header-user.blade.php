            {{-- User profile dropdown --}}
            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button @click="open = !open" type="button" class="flex items-center gap-3 rounded-lg p-1.5 hover:bg-white transition-colors focus:outline-none focus:ring-2 focus:ring-escm-primary/30">
                    <div class="hidden sm:block text-right">
                        <div class="text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</div>
                        <div class="text-xs text-slate-500">{{ auth()->user()->role_label }}</div>
                    </div>
                    <div class="h-9 w-9 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-sm font-semibold ring-2 ring-white shadow overflow-hidden">
                        @if(auth()->user()->avatar_url)
                            <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="h-full w-full object-cover">
                        @else
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        @endif
                    </div>
                    <svg class="w-4 h-4 text-slate-400 hidden sm:block transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>

                <div x-show="open" x-transition
                     class="absolute right-0 mt-2 w-56 bg-white rounded-xl border border-slate-200 shadow-lg py-1 z-50">
                    <div class="px-4 py-3 border-b border-slate-100">
                        <p class="text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-slate-500 truncate">{{ auth()->user()->email }}</p>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Mon profil
                    </a>
                    @if(in_array(auth()->user()->role, ['super_admin', 'administrateur'], true))
                    <a href="{{ route('users.index') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        Utilisateurs
                    </a>
                    @endif
                    <div class="border-t border-slate-100 mt-1 pt-1">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                Déconnexion
                            </button>
                        </form>
                    </div>
                </div>
            </div>