@php
    $title = $title ?? 'Permissions du rôle';
@endphp

@extends('layouts.app')

@section('content')
<div class="max-w-4xl space-y-4">
    <div class="flex items-center justify-between gap-3">
        <div>
            <a href="{{ route('acces.index') }}" class="text-xs font-semibold text-escm-primary hover:underline">← Retour</a>
            <h2 class="text-lg font-semibold text-slate-900 mt-1">{{ $role->name }}</h2>
            <p class="text-sm text-slate-500">Cochez les pages et actions autorisées pour ce rôle.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('acces.roles.update', $role) }}" class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        @csrf
        @method('PUT')

        <div class="px-5 py-4 border-b border-slate-100 grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Nom</label>
                <input type="text" name="name" value="{{ old('name', $role->name) }}" required class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Description</label>
                <input type="text" name="description" value="{{ old('description', $role->description) }}" class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-slate-600 mb-1">Page de démarrage</label>
                <select name="home_route" class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
                    <option value="">Dashboard général (si accessible) — sinon première page autorisée</option>
                    @foreach($homePages as $routeName => $meta)
                        <option value="{{ $routeName }}" @selected(old('home_route', $role->home_route) === $routeName)>{{ $meta['label'] }}</option>
                    @endforeach
                </select>
                <p class="mt-1.5 text-[11px] text-slate-500">Utilisée après connexion lorsque l’utilisateur n’a pas accès au dashboard général. Choisissez une page que ce rôle peut réellement ouvrir.</p>
                @error('home_route')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="px-5 py-3 border-b border-slate-100 flex flex-wrap items-center gap-3 bg-slate-50">
            <button type="button" onclick="document.querySelectorAll('.perm-check').forEach(c => c.checked = true)" class="text-xs font-semibold text-escm-primary hover:underline">Tout cocher</button>
            <button type="button" onclick="document.querySelectorAll('.perm-check').forEach(c => c.checked = false)" class="text-xs font-semibold text-slate-500 hover:underline">Tout décocher</button>
            <p class="text-[11px] text-slate-500 ml-auto">La mise à jour réapplique ces droits aux utilisateurs de ce rôle.</p>
        </div>

        <div class="divide-y divide-slate-100">
            @foreach($permissionGroups as $group => $perms)
                <div class="px-5 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">{{ $group }}</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                        @foreach($perms as $perm)
                            <label class="flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-slate-50 cursor-pointer text-sm text-slate-700">
                                <input
                                    type="checkbox"
                                    name="permissions[]"
                                    value="{{ $perm->key }}"
                                    class="perm-check rounded border-slate-300 text-escm-primary focus:ring-escm-primary"
                                    @checked(in_array($perm->key, old('permissions', $roleKeys), true))
                                >
                                {{ $perm->label }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="px-5 py-4 border-t border-slate-100 flex justify-end gap-2 bg-slate-50">
            <a href="{{ route('acces.index') }}" class="rounded-lg border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-white">Annuler</a>
            <button type="submit" class="rounded-lg bg-escm-primary text-white text-xs font-semibold px-4 py-2 hover:bg-escm-primary-dark">Enregistrer</button>
        </div>
    </form>
</div>
@endsection
