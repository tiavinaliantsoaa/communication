@php
    $title = 'Générer un lien candidat';
    $subtitle = 'Lien à usage unique pour un test';
    $preselectedTest = request('test');
@endphp

@extends('layouts.app')

@section('content')
<div class="max-w-2xl" x-data="{
    candidateId: @js(old('crm_candidate_id', '')),
    candidates: @js($candidates->map(fn($c) => ['id' => (string)$c->id, 'nom' => $c->full_name, 'email' => $c->email])->values()),
    nom: @js(old('candidat_nom', '')),
    email: @js(old('candidat_email', '')),
    pick() {
        const c = this.candidates.find(x => x.id === this.candidateId);
        if (c) { this.nom = c.nom; this.email = c.email || ''; }
    }
}">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <form action="{{ route('crm.online-tests.links.store') }}" method="POST" class="space-y-5">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Test <span class="text-red-500">*</span></label>
                <select name="online_test_id" required class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
                    <option value="">— Choisir —</option>
                    @foreach($tests as $t)
                        <option value="{{ $t->id }}" @selected((string) old('online_test_id', $preselectedTest) === (string) $t->id)
                            @disabled($t->questions_count < 1)>
                            {{ $t->nom }} ({{ $t->duree_minutes }} min · {{ $t->questions_count }} q.)
                        </option>
                    @endforeach
                </select>
                @error('online_test_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Candidat CRM <span class="text-slate-400 font-normal">(optionnel)</span></label>
                <select name="crm_candidate_id" x-model="candidateId" @change="pick()"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
                    <option value="">— Saisie manuelle —</option>
                    @foreach($candidates as $c)
                        <option value="{{ $c->id }}">{{ $c->full_name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Nom du candidat <span class="text-red-500">*</span></label>
                <input type="text" name="candidat_nom" x-model="nom" required maxlength="255"
                       class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
                @error('candidat_nom')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">E-mail</label>
                <input type="email" name="candidat_email" x-model="email" maxlength="255"
                       class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
                @error('candidat_email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <p class="text-xs text-slate-500 rounded-lg bg-amber-50 border border-amber-100 px-3 py-2">
                Le lien est <strong>à usage unique</strong>. Le test exige le plein écran ; toute sortie ou changement d’onglet annule immédiatement la tentative.
            </p>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-5 py-2.5 rounded-lg">Générer le lien</button>
                <a href="{{ route('crm.online-tests.links.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
