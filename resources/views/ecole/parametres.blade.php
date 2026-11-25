@extends('layouts.app')

@section('content')
<div class="max-w-3xl space-y-6" x-data="{
    fenetre: false,
    selection: @js($selection),
    actuelle: @js($actuelle),
    pending: '',
    choose(value) {
        if (value === this.selection) {
            this.fenetre = false;
            return;
        }
        if (value === this.actuelle) {
            this.$refs.field.value = value;
            this.$refs.form.submit();
            return;
        }
        this.pending = value;
        this.fenetre = true;
    },
    fermerFenetre() {
        this.fenetre = false;
    },
    appliquerAnnee() {
        if (!this.pending || this.pending === this.actuelle || this.pending === this.selection) {
            this.fenetre = false;
            return;
        }
        this.$refs.field.value = this.pending;
        this.$refs.form.submit();
    }
}">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <h3 class="text-sm font-semibold text-slate-900 mb-1">Année à afficher</h3>
        <p class="text-sm text-slate-500 mb-4">Cette année est utilisée dans toute la gestion école : groupes, étudiants, emplois du temps et absences.</p>
        <div class="space-y-2">
            @foreach($annees as $annee)
                <button type="button"
                        @click="choose(@js($annee))"
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
        <input x-ref="field" type="hidden" name="annee_universitaire" value="{{ $selection }}">
    </form>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <h3 class="text-sm font-semibold text-slate-900 mb-1">Liste des salles de classe</h3>
        <p class="text-sm text-slate-500 mb-4">Indiquez combien d’étudiants chaque salle peut accueillir. Un créneau est refusé si le groupe choisi est plus nombreux.</p>
        <form method="POST" action="{{ route('ecole.parametres.salles.store') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @csrf
            <div>
                <label for="salle-nom" class="block text-sm font-medium text-slate-700 mb-1.5">Salle de classe</label>
                <input id="salle-nom" type="text" name="nom" value="{{ old('nom') }}" required maxlength="120" placeholder="Ex. Grasse" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-escm-primary focus:ring-escm-primary">
                @error('nom')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="salle-capacite" class="block text-sm font-medium text-slate-700 mb-1.5">Nombre d’étudiants</label>
                <input id="salle-capacite" type="number" name="capacite" value="{{ old('capacite') }}" required min="1" max="999" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-escm-primary focus:ring-escm-primary">
                @error('capacite')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <button type="submit" class="bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-4 py-2.5 rounded-lg">Ajouter la salle</button>
            </div>
        </form>
        <div class="mt-5 divide-y divide-slate-100 border-t border-slate-100">
            @forelse($salles as $salle)
                <div class="flex items-center justify-between gap-3 py-3">
                    <div>
                        <p class="text-sm font-medium text-slate-900">{{ $salle->nom }}</p>
                        <p class="text-xs text-slate-500">{{ $salle->capacite }} {{ $salle->capacite > 1 ? 'étudiants' : 'étudiant' }}</p>
                    </div>
                    <form method="POST" action="{{ route('ecole.parametres.salles.destroy', $salle) }}" onsubmit="return confirm('Supprimer cette salle ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs font-semibold text-slate-500 hover:text-red-600">Supprimer</button>
                    </form>
                </div>
            @empty
                <p class="py-4 text-sm text-slate-500">Aucune salle de classe pour le moment.</p>
            @endforelse
        </div>
    </div>

    <div x-show="fenetre"
         x-cloak
         class="fixed inset-0 z-[80] flex items-center justify-center p-4 bg-slate-900/60"
         @keydown.escape.window="fermerFenetre()"
         @click="fermerFenetre()">
        <div class="w-full max-w-md rounded-xl bg-white shadow-xl border border-slate-200 p-6" @click.stop>
            <h3 class="text-base font-semibold text-slate-900 mb-2">Année non actuelle</h3>
            <p class="text-sm font-medium text-slate-900 mb-2" x-text="pending"></p>
            <p class="text-sm text-slate-600">Année choisi non actuelle, les données et modifications seront gardées pour l’année non actuelle choisi.</p>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" @click.stop="fermerFenetre()" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Annuler</button>
                <button type="button" @click.stop="appliquerAnnee()" class="rounded-lg bg-escm-primary px-4 py-2 text-sm font-medium text-white hover:bg-escm-primary-dark">Continuer</button>
            </div>
        </div>
    </div>
</div>
@endsection
