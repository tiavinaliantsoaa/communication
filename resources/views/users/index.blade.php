@php
    $title = 'Gestion des utilisateurs';
    $subtitle = 'Administration des comptes utilisateurs';
@endphp

@extends('layouts.app')

@section('content')
<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 mb-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h2 class="text-sm font-semibold text-slate-900">Départements</h2>
            <p class="mt-1 text-xs text-slate-500 max-w-xl">Les menus restent les mêmes pour tout le monde. Chaque utilisateur ne voit que les données du département qui lui est assigné.</p>
        </div>
        <form action="{{ route('departements.store') }}" method="POST" class="flex w-full gap-2 lg:max-w-md">
            @csrf
            <input type="text" name="nom" value="{{ old('nom') }}" required placeholder="Nouveau département"
                   class="min-w-0 flex-1 rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
            <button type="submit" class="shrink-0 bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg">Créer</button>
        </form>
    </div>
    @error('nom')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror

    <div class="mt-4 divide-y divide-slate-100 border-t border-slate-100">
        @foreach($departements as $departement)
            <div class="flex flex-col gap-3 py-3 sm:flex-row sm:items-center">
                <form action="{{ route('departements.update', $departement) }}" method="POST" class="flex min-w-0 flex-1 items-center gap-2">
                    @csrf @method('PUT')
                    <input type="text" name="nom" value="{{ $departement->nom }}" required
                           class="min-w-0 flex-1 rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
                    <button type="submit" class="shrink-0 text-sm font-medium text-escm-primary hover:text-escm-primary-dark">Renommer</button>
                </form>
                <div class="flex items-center justify-between gap-3 sm:justify-end">
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $departement->badgeClasses() }}">
                        {{ $departement->users_count }} utilisateur{{ $departement->users_count > 1 ? 's' : '' }}
                    </span>
                    @unless($departement->is_system)
                        <form action="{{ route('departements.destroy', $departement) }}" method="POST" onsubmit="return confirm('Supprimer ce département ?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-sm text-slate-400 hover:text-red-600">Supprimer</button>
                        </form>
                    @endunless
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="flex items-center justify-between gap-3 mb-6">
    <form method="GET" action="{{ route('users.index') }}" class="flex items-center gap-2">
        <label for="filtre-departement" class="text-sm text-slate-600">Filtrer</label>
        <select id="filtre-departement" name="departement" onchange="this.form.submit()" class="rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
            <option value="">Tous les départements</option>
            @foreach($departements as $departement)
                <option value="{{ $departement->id }}" @selected((int) $departementId === (int) $departement->id)>{{ $departement->nom }}</option>
            @endforeach
        </select>
    </form>
    <a href="{{ route('users.create') }}" class="inline-flex items-center gap-2 bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-4 py-2.5 rounded-lg transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Nouvel utilisateur
    </a>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100 bg-slate-50/50">
                    <th class="px-5 py-3">Nom</th>
                    <th class="px-3 py-3">Email</th>
                    <th class="px-3 py-3">Rôle</th>
                    <th class="px-3 py-3">Département</th>
                    <th class="px-3 py-3">Date création</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($users as $user)
                <tr class="hover:bg-slate-50/50">
                    <td class="px-5 py-3 font-medium text-slate-900">{{ $user->name }}</td>
                    <td class="px-3 py-3 text-slate-600">{{ $user->email }}</td>
                    <td class="px-3 py-3">
                        <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-600/20">
                            {{ $user->role_label }}
                        </span>
                    </td>
                    <td class="px-3 py-3">
                        @if($user->departement)
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $user->departement->badgeClasses() }}">
                                {{ $user->departement->nom }}
                            </span>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="px-3 py-3 text-slate-600">{{ $user->created_at->format('d/m/Y') }}</td>
                    <td class="px-5 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('users.show', $user) }}" class="p-1.5 text-slate-400 hover:text-escm-primary rounded" title="Voir">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                            <a href="{{ route('users.edit', $user) }}" class="p-1.5 text-slate-400 hover:text-escm-primary rounded" title="Modifier">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            @if($user->id !== auth()->id())
                            <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('Supprimer cet utilisateur ?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-1.5 text-slate-400 hover:text-red-600 rounded" title="Supprimer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">Aucun utilisateur trouvé.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
    <div class="px-5 py-3 border-t border-slate-100">{{ $users->links() }}</div>
    @endif
</div>
@endsection
