@extends('layouts.app')

@section('content')
<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
    <h3 class="text-sm font-semibold text-slate-900 mb-1">Compte enseignant</h3>
    <p class="text-sm text-slate-500 mb-4">Choisissez un enseignant pour voir son tableau de bord et uniquement les créneaux que l’équipe pédagogique a planifiés pour lui.</p>
    <div class="divide-y divide-slate-100 border-t border-slate-100">
        @forelse($enseignants as $enseignant)
            <a href="{{ route('ecole.compte-enseignant', ['enseignant' => $enseignant->id]) }}" class="flex items-center justify-between gap-3 py-3 text-sm font-medium text-slate-900 hover:text-escm-primary">
                <span>{{ $enseignant->nom_complet }}</span>
                <span class="text-xs font-semibold text-escm-primary">Voir</span>
            </a>
        @empty
            <p class="py-4 text-sm text-slate-500">Aucun enseignant pour le moment.</p>
        @endforelse
    </div>
</div>
@endsection
