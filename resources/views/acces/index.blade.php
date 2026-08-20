@php
    $title = $title ?? 'Gestion d’accès';
@endphp

@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Gestion d’accès</h2>
            <p class="text-sm text-slate-500 mt-0.5">Créez des rôles et définissez les pages / actions autorisées pour chaque utilisateur.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        {{-- Rôles --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
                <h3 class="text-sm font-semibold text-slate-900">Rôles</h3>
                <button type="button" onclick="document.getElementById('create-role-panel').classList.toggle('hidden')" class="text-xs font-semibold text-escm-primary hover:underline">+ Nouveau rôle</button>
            </div>

            <div id="create-role-panel" class="hidden border-b border-slate-100 bg-slate-50 px-5 py-4">
                <form method="POST" action="{{ route('acces.roles.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Nom du rôle</label>
                        <input type="text" name="name" required maxlength="100" class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary" placeholder="Ex. Assistant marketing">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Description <span class="text-slate-400">(optionnel)</span></label>
                        <input type="text" name="description" maxlength="255" class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
                    </div>
                    <p class="text-[11px] text-slate-500">Après création, ouvrez le rôle pour cocher les permissions.</p>
                    <button type="submit" class="rounded-lg bg-escm-primary text-white text-xs font-semibold px-4 py-2 hover:bg-escm-primary-dark">Créer</button>
                </form>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($roles as $role)
                    <div class="px-5 py-3 flex items-center justify-between gap-3 hover:bg-slate-50/80">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-800 truncate">
                                {{ $role->name }}
                                @if($role->is_system)
                                    <span class="ml-1 inline-flex items-center rounded bg-slate-100 text-slate-500 text-[10px] font-semibold px-1.5 py-0.5">Système</span>
                                @endif
                            </p>
                            <p class="text-xs text-slate-500 mt-0.5">{{ $role->permissions_count }} permission(s)@if($role->description) · {{ $role->description }}@endif</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <a href="{{ route('acces.roles.edit', $role) }}" class="text-xs font-semibold text-escm-primary hover:underline">Permissions</a>
                            @unless($role->is_system)
                                <form method="POST" action="{{ route('acces.roles.destroy', $role) }}" onsubmit="return confirm('Supprimer ce rôle ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-red-600 hover:underline">Suppr.</button>
                                </form>
                            @endunless
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-sm text-slate-500">Aucun rôle.</div>
                @endforelse
            </div>
        </div>

        {{-- Utilisateurs --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100">
                <h3 class="text-sm font-semibold text-slate-900">Accès par utilisateur</h3>
                <p class="text-xs text-slate-500 mt-0.5">Cochez les pages et actions pour chaque compte.</p>
            </div>
            <div class="divide-y divide-slate-100 max-h-[32rem] overflow-y-auto">
                @forelse($users as $user)
                    <a href="{{ route('acces.users.edit', $user) }}" class="px-5 py-3 flex items-center gap-3 hover:bg-slate-50/80 transition-colors">
                        <x-user-avatar :user="$user" size="sm" />
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-800 truncate">{{ $user->name }}</p>
                            <p class="text-xs text-slate-500 truncate">{{ $user->email }} · {{ $user->role_label }}</p>
                        </div>
                        <span class="text-xs font-semibold text-escm-primary shrink-0">Configurer →</span>
                    </a>
                @empty
                    <div class="px-5 py-8 text-center text-sm text-slate-500">Aucun utilisateur.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
