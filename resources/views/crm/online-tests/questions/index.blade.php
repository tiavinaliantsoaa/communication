@php
    $title = 'Questions — '.$test->nom;
    $subtitle = 'Ajoutez les questions et leurs notes';
@endphp

@extends('layouts.app')

@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <a href="{{ route('crm.online-tests.show', $test) }}" class="text-sm text-slate-500 hover:text-slate-800">← {{ $test->nom }}</a>
    <p class="text-xs text-slate-500">Durée : {{ $test->duree_minutes }} min · {{ $test->questions->count() }} question(s)</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    @if(auth()->user()->canAccess('online_tests.update'))
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 sticky top-4" x-data="{ type: @js(old('type', 'qcm')) }">
            <h3 class="text-sm font-semibold text-slate-800 mb-3">Nouvelle question</h3>
            <form action="{{ route('crm.online-tests.questions.store', $test) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Énoncé <span class="text-red-500">*</span></label>
                    <textarea name="enonce" rows="3" required maxlength="5000"
                              class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary"
                              placeholder="Texte de la question…">{{ old('enonce') }}</textarea>
                    @error('enonce')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Type</label>
                    <select name="type" x-model="type" class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
                        @foreach($types as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="type === 'qcm'" x-cloak>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Options (une par ligne)</label>
                    <textarea name="options_text" rows="4"
                              class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary"
                              placeholder="Option A&#10;Option B&#10;Option C">{{ old('options_text') }}</textarea>
                    @error('options_text')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    <label class="block text-xs font-medium text-slate-600 mt-3 mb-1.5">Bonne réponse <span class="text-slate-400">(optionnel, pour notation auto)</span></label>
                    <input type="text" name="bonne_reponse" value="{{ old('bonne_reponse') }}" maxlength="255"
                           class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary"
                           placeholder="Doit correspondre exactement à une option">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Note / points <span class="text-red-500">*</span></label>
                    <input type="number" name="points" value="{{ old('points', 1) }}" required min="0" step="0.5"
                           class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
                    @error('points')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="w-full bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-4 py-2.5 rounded-lg">Ajouter</button>
            </form>
        </div>
    </div>
    @endif

    <div class="{{ auth()->user()->canAccess('online_tests.update') ? 'lg:col-span-2' : 'lg:col-span-3' }} space-y-3">
        @forelse($test->questions as $i => $question)
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Question {{ $i + 1 }} · {{ $question->type_label }} · {{ rtrim(rtrim(number_format((float)$question->points, 2, ',', ' '), '0'), ',') }} pts</p>
                    <p class="text-sm font-medium text-slate-900 whitespace-pre-wrap">{{ $question->enonce }}</p>
                    @if($question->type === 'qcm')
                        <ul class="mt-2 space-y-1">
                            @foreach($question->optionsList() as $opt)
                                <li class="text-sm text-slate-600 flex items-center gap-2">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $question->bonne_reponse === $opt ? 'bg-green-500' : 'bg-slate-300' }}"></span>
                                    {{ $opt }}
                                    @if($question->bonne_reponse === $opt)<span class="text-[11px] text-green-600 font-medium">bonne réponse</span>@endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                @if(auth()->user()->canAccess('online_tests.update'))
                <form action="{{ route('crm.online-tests.questions.destroy', [$test, $question]) }}" method="POST" onsubmit="return confirm('Supprimer cette question ?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-xs text-slate-400 hover:text-red-600">Suppr.</button>
                </form>
                @endif
            </div>
        </div>
        @empty
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-5 py-10 text-center text-slate-500 text-sm">
            Aucune question. Ajoutez-en pour pouvoir générer des liens candidats.
        </div>
        @endforelse
    </div>
</div>
@endsection
