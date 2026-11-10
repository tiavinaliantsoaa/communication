@php
    $title = 'Création des tests';
    $subtitle = 'Gérez les tests d’admission, leurs questions et leur durée';
@endphp

@extends('layouts.app')

@section('content')
@if(auth()->user()->canAccess('online_tests.create'))
    <x-page-actions :create-route="route('crm.online-tests.create')" create-label="Ajouter" />
@endif

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100 bg-slate-50/50">
                    <th class="px-5 py-3">Nom</th>
                    <th class="px-3 py-3">Durée (min)</th>
                    <th class="px-3 py-3">Questions</th>
                    <th class="px-3 py-3">Actif</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($tests as $test)
                <tr class="hover:bg-slate-50/50">
                    <td class="px-5 py-3 font-medium text-slate-900">
                        <a href="{{ route('crm.online-tests.show', $test) }}" class="hover:text-escm-primary">{{ $test->nom }}</a>
                    </td>
                    <td class="px-3 py-3 text-slate-600">{{ $test->duree_minutes }}</td>
                    <td class="px-3 py-3 text-slate-600">{{ $test->questions_count }}</td>
                    <td class="px-3 py-3">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $test->actif ? 'bg-green-50 text-green-700 ring-green-600/20' : 'bg-slate-100 text-slate-600 ring-slate-500/20' }}">
                            {{ $test->actif ? 'Oui' : 'Non' }}
                        </span>
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('crm.online-tests.questions.index', $test) }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50" title="Questions">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                                Questions
                            </a>
                            @if(auth()->user()->canAccess('online_tests.update'))
                                <a href="{{ route('crm.online-tests.edit', $test) }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Modifier</a>
                            @endif
                            @if(auth()->user()->canAccess('online_tests.delete'))
                                <form action="{{ route('crm.online-tests.destroy', $test) }}" method="POST" onsubmit="return confirm('Supprimer ce test et toutes ses questions ?')">
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
                <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">Aucun test pour le moment.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($tests->hasPages())
    <div class="px-5 py-3 border-t border-slate-100">{{ $tests->links() }}</div>
    @endif
</div>
@endsection
