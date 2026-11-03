@php
    $budget = $budget ?? null;
    $isEdit = $edit ?? false;
    $categories = $categories ?? collect();
    $lignes = $lignes ?? [['depense_categorie_id' => '', 'montant' => '']];
@endphp

<div
    class="space-y-5"
    x-data="{
        montant: '{{ old('montant', $budget?->montant ?? '') }}',
        lignes: @js($lignes),
        addLigne() {
            this.lignes.push({ depense_categorie_id: '', montant: '' });
        },
        removeLigne(index) {
            if (this.lignes.length <= 1) {
                this.lignes = [{ depense_categorie_id: '', montant: '' }];
                return;
            }
            this.lignes.splice(index, 1);
        },
        get totalLignes() {
            return this.lignes.reduce((sum, row) => sum + (parseFloat(row.montant) || 0), 0);
        },
        get restant() {
            return (parseFloat(this.montant) || 0) - this.totalLignes;
        }
    }"
>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Montant (Ar)</label>
        <input type="number" name="montant" x-model="montant" required min="0" step="1"
               class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
        @error('montant')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Année</label>
            <input type="number" name="annee" value="{{ old('annee', $budget?->annee ?? now()->year) }}" required min="2020" max="2100"
                   class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
            @error('annee')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Mois</label>
            <select name="mois" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
                @foreach(range(1, 12) as $m)
                    <option value="{{ $m }}" @selected(old('mois', $budget?->mois ?? now()->month) == $m)>
                        {{ \Carbon\Carbon::create(null, $m, 1)->locale('fr')->isoFormat('MMMM') }}
                    </option>
                @endforeach
            </select>
            @error('mois')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 space-y-3">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold text-slate-800">Répartition par catégories</h3>
                <p class="text-xs text-slate-500 mt-0.5">Détaillez le budget mensuel par catégorie de dépense.</p>
            </div>
            <button type="button" @click="addLigne()" class="shrink-0 text-xs font-semibold text-escm-primary hover:underline">
                + Ajouter une ligne
            </button>
        </div>

        @if($categories->isEmpty())
            <p class="text-xs text-amber-700 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">
                Aucune catégorie disponible.
                <a href="{{ route('budget-annuels.index') }}" class="font-semibold underline">Créez-les dans le budget annuel détaillé</a>.
            </p>
        @endif

        <div class="space-y-2">
            <template x-for="(ligne, index) in lignes" :key="index">
                <div class="grid grid-cols-1 sm:grid-cols-[1fr_8rem_auto] gap-2 items-end bg-white rounded-lg border border-slate-200 p-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Catégorie</label>
                        <select :name="'lignes['+index+'][depense_categorie_id]'" x-model="ligne.depense_categorie_id"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
                            <option value="">— Choisir —</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->nom }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Montant</label>
                        <input type="number" min="0" step="1" :name="'lignes['+index+'][montant]'" x-model="ligne.montant"
                               class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary"
                               placeholder="0">
                    </div>
                    <button type="button" @click="removeLigne(index)" class="text-xs text-slate-400 hover:text-red-600 px-2 py-2" title="Retirer">
                        Retirer
                    </button>
                </div>
            </template>
        </div>

        @error('lignes')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
        @error('lignes.*')<p class="text-sm text-red-600">{{ $message }}</p>@enderror

        <div class="flex flex-wrap items-center justify-between gap-2 text-xs pt-1">
            <p class="text-slate-600">
                Réparti :
                <span class="font-semibold text-slate-900" x-text="new Intl.NumberFormat('fr-FR').format(totalLignes) + ' Ar'"></span>
            </p>
            <p :class="Math.abs(restant) < 0.01 ? 'text-green-700' : (restant < 0 ? 'text-red-600' : 'text-amber-700')">
                <span x-text="restant < -0.009 ? 'Dépassement : ' : 'Non réparti : '"></span>
                <span class="font-semibold" x-text="new Intl.NumberFormat('fr-FR').format(Math.abs(restant)) + ' Ar'"></span>
            </p>
        </div>
    </div>
</div>
