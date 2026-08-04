@php
    $depense = $depense ?? null;
    $canApprove = $canApprove ?? (auth()->user()?->canApproveDepense() ?? false);
    $currentStatut = old('statut', $depense?->statut ?? 'en_attente');
    $currentMode = old('mode_paiement', $depense?->mode_paiement);
    $isLockedApproved = ! $canApprove && $depense && $depense->statut === 'valide';
@endphp

<div
    class="space-y-4"
    x-data="{
        statut: @js($currentStatut),
        modePaiement: @js($currentMode ?? ''),
        get showPaiement() { return this.statut === 'paye'; },
        get showReste() { return this.showPaiement && this.modePaiement === 'acompte'; },
    }"
>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Date</label>
            <input type="date" name="date_depense" value="{{ old('date_depense', $depense?->date_depense?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required
                   class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
            @error('date_depense')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Montant (Ar)</label>
            <input type="number" name="montant" value="{{ old('montant', $depense?->montant) }}" required min="0" step="1"
                   class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
            @error('montant')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Fournisseur</label>
        <input type="text" name="fournisseur" list="fournisseurs-list" value="{{ old('fournisseur', $depense?->fournisseur) }}" required
               class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
        <datalist id="fournisseurs-list">
            @foreach($fournisseurs as $nom)
                <option value="{{ $nom }}">
            @endforeach
        </datalist>
        @error('fournisseur')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Objet</label>
        <input type="text" name="objet" value="{{ old('objet', $depense?->objet) }}" required
               class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
        @error('objet')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Campagne</label>
        <input type="text" name="campagne" list="campagnes-list" value="{{ old('campagne', $depense?->campagne) }}"
               class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
        <datalist id="campagnes-list">
            @foreach($campagnes as $nom)
                <option value="{{ $nom }}">
            @endforeach
        </datalist>
        @error('campagne')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Catégorie</label>
            <select name="categorie" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
                @foreach($categories as $value => $label)
                    <option value="{{ $value }}" @selected(old('categorie', $depense?->categorie) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('categorie')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Statut</label>

            @if($isLockedApproved)
                <input type="hidden" name="statut" value="valide">
                <div class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm text-blue-800">
                    Approuvé <span class="text-blue-600 text-xs">(modifiable uniquement par un Super Admin)</span>
                </div>
            @else
                <select
                    name="statut"
                    required
                    x-model="statut"
                    @change="if (statut !== 'paye') { modePaiement = ''; }"
                    class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm"
                >
                    @foreach($statuts as $value => $label)
                        <option value="{{ $value }}" @selected($currentStatut === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @unless($canApprove)
                    <p class="mt-1.5 text-xs text-slate-500">Seul un Super Admin peut passer le statut à « Approuvé ».</p>
                @endunless
            @endif
            @error('statut')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>

    <div x-show="showPaiement" x-cloak class="rounded-lg border border-slate-200 bg-slate-50/80 p-4 space-y-3">
        <p class="text-sm font-medium text-slate-700">Mode de paiement</p>
        <div class="flex flex-wrap items-center gap-4">
            <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                <input
                    type="radio"
                    name="mode_paiement"
                    value="acompte"
                    x-model="modePaiement"
                    class="border-slate-300 text-escm-primary focus:ring-escm-primary"
                >
                <span>Acompte</span>
            </label>
            <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                <input
                    type="radio"
                    name="mode_paiement"
                    value="totalite"
                    x-model="modePaiement"
                    class="border-slate-300 text-escm-primary focus:ring-escm-primary"
                >
                <span>Totalité</span>
            </label>
        </div>
        @error('mode_paiement')<p class="text-sm text-red-600">{{ $message }}</p>@enderror

        <div x-show="showReste" x-cloak>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Reste à payer (Ar)</label>
            <input
                type="number"
                name="reste_a_payer"
                value="{{ old('reste_a_payer', $depense?->reste_a_payer) }}"
                min="0"
                step="1"
                :required="showReste"
                class="w-full sm:max-w-xs rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm"
            >
            @error('reste_a_payer')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>
</div>
