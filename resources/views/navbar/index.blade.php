@php
    $title = $title ?? 'Gestion liste navbar';
@endphp

@extends('layouts.app')

@section('content')
<div class="space-y-4">
    <div>
        <h2 class="text-lg font-semibold text-slate-900">Gestion liste navbar</h2>
        <p class="text-sm text-slate-500 mt-0.5">Cochez les menus visibles pour chaque département. Une section décochée disparaît de la barre de navigation.</p>
    </div>

    <form method="POST" action="{{ route('navbar.update') }}" class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        @csrf
        @method('PUT')

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50 text-left">
                        <th class="sticky left-0 bg-slate-50 px-5 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Menu</th>
                        @foreach($departements as $departement)
                            <th class="px-4 py-3 text-center min-w-[9rem]">
                                <input type="hidden" name="departements[]" value="{{ $departement->id }}">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $departement->badgeClasses() }}">{{ $departement->nom }}</span>
                                <div class="mt-2 flex items-center justify-center gap-2 text-[11px] font-semibold normal-case tracking-normal">
                                    <button type="button" class="text-escm-primary hover:underline" onclick="document.querySelectorAll('.menu-{{ $departement->id }}').forEach(c => c.checked = true)">Tout</button>
                                    <button type="button" class="text-slate-500 hover:underline" onclick="document.querySelectorAll('.menu-{{ $departement->id }}').forEach(c => c.checked = false)">Aucun</button>
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($sections as $group => $items)
                        <tr class="bg-slate-50/80">
                            <td colspan="{{ $departements->count() + 1 }}" class="px-5 py-2 text-[10px] font-semibold uppercase tracking-wider text-slate-500">{{ $group }}</td>
                        </tr>
                        @foreach($items as $item)
                            <tr class="border-t border-slate-100">
                                <td class="sticky left-0 bg-white px-5 py-2.5 font-medium text-slate-800">{{ $item['label'] }}</td>
                                @foreach($departements as $departement)
                                    <td class="px-4 py-2.5 text-center">
                                        <input
                                            type="checkbox"
                                            name="menus[{{ $departement->id }}][]"
                                            value="{{ $item['key'] }}"
                                            class="menu-{{ $departement->id }} rounded border-slate-300 text-escm-primary focus:ring-escm-primary"
                                            @checked($enabled[$departement->id][$item['key']] ?? true)
                                        >
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-5 py-4 border-t border-slate-100 flex justify-end bg-slate-50">
            <button type="submit" class="rounded-lg bg-escm-primary text-white text-xs font-semibold px-4 py-2 hover:bg-escm-primary-dark">Enregistrer</button>
        </div>
    </form>
</div>
@endsection
