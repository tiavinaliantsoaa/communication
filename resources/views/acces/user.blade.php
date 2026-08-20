@php
    $title = $title ?? 'Accès utilisateur';
@endphp

@extends('layouts.app')

@section('content')
<div class="max-w-4xl space-y-4">
    <div>
        <a href="{{ route('acces.index') }}" class="text-xs font-semibold text-escm-primary hover:underline">← Retour</a>
        <div class="mt-2 flex items-center gap-3">
            <x-user-avatar :user="$user" size="md" />
            <div>
                <h2 class="text-lg font-semibold text-slate-900">{{ $user->name }}</h2>
                <p class="text-sm text-slate-500">{{ $user->email }}</p>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('acces.users.update', $user) }}" class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden" x-data="{
        applyRole() {
            const sel = document.getElementById('role-select');
            const opt = sel.options[sel.selectedIndex];
            const keys = JSON.parse(opt.dataset.keys || '[]');
            document.querySelectorAll('.perm-check').forEach(c => {
                c.checked = keys.includes(c.value);
            });
        }
    }">
        @csrf
        @method('PUT')

        <div class="px-5 py-4 border-b border-slate-100">
            <label class="block text-xs font-medium text-slate-600 mb-1">Rôle</label>
            <select
                id="role-select"
                name="role"
                required
                class="w-full sm:w-80 rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary"
                @change="applyRole()"
            >
                @foreach($roles as $role)
                    <option
                        value="{{ $role->slug }}"
                        data-keys='@json($role->permissions->pluck('key')->values())'
                        @selected(old('role', $user->role) === $role->slug)
                    >{{ $role->name }}</option>
                @endforeach
            </select>
            <p class="text-[11px] text-slate-500 mt-1.5">Changer de rôle pré-remplit les cases. Vous pouvez ensuite ajuster manuellement.</p>
        </div>

        <div class="px-5 py-3 border-b border-slate-100 flex flex-wrap items-center gap-3 bg-slate-50">
            <button type="button" onclick="document.querySelectorAll('.perm-check').forEach(c => c.checked = true)" class="text-xs font-semibold text-escm-primary hover:underline">Tout cocher</button>
            <button type="button" onclick="document.querySelectorAll('.perm-check').forEach(c => c.checked = false)" class="text-xs font-semibold text-slate-500 hover:underline">Tout décocher</button>
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
                                    @checked(in_array($perm->key, old('permissions', $userKeys), true))
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
            <button type="submit" class="rounded-lg bg-escm-primary text-white text-xs font-semibold px-4 py-2 hover:bg-escm-primary-dark">Enregistrer les accès</button>
        </div>
    </form>
</div>
@endsection
