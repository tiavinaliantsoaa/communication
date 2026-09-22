@php
    $title = 'Détails utilisateur';
    $subtitle = $user->name;
@endphp

@extends('layouts.app')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <dl class="space-y-4">
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Nom</dt>
                <dd class="mt-1 text-sm font-medium text-slate-900">{{ $user->name }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Email</dt>
                <dd class="mt-1 text-sm text-slate-900">{{ $user->email }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Rôle</dt>
                <dd class="mt-1"><span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700">{{ $user->role_label }}</span></dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Département</dt>
                <dd class="mt-1">
                    @if($user->departement)
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $user->departement->badgeClasses() }}">{{ $user->departement->nom }}</span>
                    @else
                        <span class="text-sm text-slate-500">Non assigné</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Date de création</dt>
                <dd class="mt-1 text-sm text-slate-900">{{ $user->created_at->format('d/m/Y à H:i') }}</dd>
            </div>
        </dl>
        <div class="flex items-center gap-3 mt-6 pt-6 border-t border-slate-100">
            <a href="{{ route('users.edit', $user) }}" class="bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg">Modifier</a>
            <a href="{{ route('users.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Retour à la liste</a>
        </div>
    </div>
</div>
@endsection
