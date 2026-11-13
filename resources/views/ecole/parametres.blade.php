@extends('layouts.app')

@section('content')
<div class="max-w-xl" x-data='{
    open: false,
    year: @js($selection),
    actuelle: @js($actuelle),
    choose(value) {
        this.year = value;
        this.$refs.field.value = value;
        if (value === this.actuelle) {
            this.$refs.form.submit();
        } else {
            this.open = true;
        }
    }
}'>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <h3 class="text-sm font-semibold text-slate-900 mb-1">Année à afficher</h3>
        <p class="text-sm text-slate-500 mb-4">Cette année est utilisée dans toute la gestion école : groupes, étudiants, emplois du temps et absences.</p>
        <div class="space-y-2">
            @foreach($annees as $annee)
                <button type="button"
                        @click='choose(@js($annee))'
                        class="flex w-full items-center justify-between gap-3 rounded-lg border px-4 py-3 text-left text-sm transition-colors {{ $annee === $selection ? 'border-escm-primary bg-blue-50 text-slate-900' : 'border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                    <span class="font-medium">{{ $annee }}</span>
                    @if($annee === $actuelle)
                        <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Actuelle</span>
                    @endif
                </button>
            @endforeach
        </div>
    </div>

    <form x-ref="form" method="POST" action="{{ route('ecole.annee') }}" class="hidden">
        @csrf
        <input x-ref="field" type="hidden" name="annee_universitaire" :value="year">
    </form>

    <div x-show="open" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-slate-900/60" @keydown.escape.window="open = false">
        <div class="w-full max-w-md rounded-xl bg-white shadow-xl border border-slate-200 p-6" @click.outside="open = false">
            <h3 class="text-base font-semibold text-slate-900 mb-2">Année non actuelle</h3>
            <p class="text-sm text-slate-600">Année choisi non actuelle, les données et modifications seront gardées pour l’année non actuelle choisi.</p>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" @click="open = false" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Annuler</button>
                <button type="button" @click="$refs.form.submit()" class="rounded-lg bg-escm-primary px-4 py-2 text-sm font-medium text-white hover:bg-escm-primary-dark">Continuer</button>
            </div>
        </div>
    </div>
</div>
@endsection
