@php
    $title = 'Résultats des tests';
    $subtitle = 'Consultez les réponses, notez et validez';
@endphp

@extends('layouts.app')

@section('content')
<div class="mb-6">
    <form method="GET" class="flex items-center gap-2">
        <select name="statut" onchange="this.form.submit()" class="rounded-lg border-slate-200 text-sm focus:border-escm-primary focus:ring-escm-primary">
            <option value="">Tous les statuts</option>
            @foreach($statuts as $key => $label)
                @if($key !== 'en_attente')
                    <option value="{{ $key }}" @selected($statutActif === $key)>{{ $label }}</option>
                @endif
            @endforeach
        </select>
    </form>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100 bg-slate-50/50">
                    <th class="px-5 py-3">Candidat</th>
                    <th class="px-3 py-3">Test</th>
                    <th class="px-3 py-3">Statut</th>
                    <th class="px-3 py-3 text-right">Score</th>
                    <th class="px-3 py-3">Validé</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($results as $row)
                <tr class="hover:bg-slate-50/50">
                    <td class="px-5 py-3 font-medium text-slate-900">{{ $row->candidat_nom }}</td>
                    <td class="px-3 py-3 text-slate-600">{{ $row->test?->nom ?? '—' }}</td>
                    <td class="px-3 py-3">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $row->statut_color }}">{{ $row->statut_label }}</span>
                    </td>
                    <td class="px-3 py-3 text-right font-medium text-slate-900">
                        @if($row->score_final !== null)
                            {{ rtrim(rtrim(number_format((float)$row->score_final, 2, ',', ' '), '0'), ',') }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-3 py-3">
                        @if($row->valide)
                            <span class="text-xs font-medium text-green-700">Oui</span>
                        @else
                            <span class="text-xs text-slate-400">Non</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right">
                        <a href="{{ route('crm.online-tests.results.show', $row) }}" class="text-xs font-medium text-escm-primary hover:underline">Voir / noter</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">Aucun résultat pour le moment.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($results->hasPages())
    <div class="px-5 py-3 border-t border-slate-100">{{ $results->links() }}</div>
    @endif
</div>
@endsection
