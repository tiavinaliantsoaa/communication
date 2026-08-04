@php
    $mouvement = $mouvement ?? null;
    $types = $types ?? \App\Models\StockMouvement::TYPES;
    $defaultType = $selectedType ?? 'sortie';
    $defaultStockId = $selectedStockId ?? null;
@endphp

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Article (flyers, brochures…)</label>
    <select name="stock_id" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
        <option value="">Sélectionner un article</option>
        @foreach($stocks as $stock)
            <option value="{{ $stock->id }}" @selected(old('stock_id', $mouvement?->stock_id ?? $defaultStockId) == $stock->id)>
                {{ $stock->article }} — stock actuel : {{ $stock->quantite }}
            </option>
        @endforeach
    </select>
    @error('stock_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    @if($stocks->isEmpty())
        <p class="mt-1.5 text-xs text-amber-600">Aucun article en stock. <a href="{{ route('stocks.create') }}" class="underline font-medium">Créer un article</a> d’abord.</p>
    @endif
</div>

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Type de mouvement</label>
    <div class="flex items-center gap-4">
        @foreach($types as $value => $label)
            <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                <input type="radio" name="type" value="{{ $value }}"
                       @checked(old('type', $mouvement?->type ?? $defaultType) === $value)
                       class="text-escm-primary focus:ring-escm-primary border-slate-300">
                <span class="font-semibold {{ $value === 'entree' ? 'text-green-700' : 'text-orange-700' }}">{{ $label }}</span>
            </label>
        @endforeach
    </div>
    @error('type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    <p class="mt-1.5 text-xs text-slate-500">Entrée = ajoute au stock · Sortie = déduit automatiquement du stock.</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Quantité</label>
        <input type="number" name="quantite" value="{{ old('quantite', $mouvement?->quantite ?? 1) }}" required min="1"
               class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
        @error('quantite')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Date</label>
        <input type="date" name="date_mouvement" value="{{ old('date_mouvement', $mouvement?->date_mouvement?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required
               class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
        @error('date_mouvement')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Motif <span class="text-slate-400 font-normal">(optionnel)</span></label>
    <input type="text" name="motif" value="{{ old('motif', $mouvement?->motif) }}"
           class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm"
           placeholder="Ex. Distribution salon, Réception imprimerie…">
    @error('motif')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Référence <span class="text-slate-400 font-normal">(optionnel)</span></label>
    <input type="text" name="reference" value="{{ old('reference', $mouvement?->reference) }}"
           class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm"
           placeholder="Ex. BL-2026-014, Événement Portes Ouvertes…">
    @error('reference')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Notes <span class="text-slate-400 font-normal">(optionnel)</span></label>
    <textarea name="notes" rows="2"
              class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm"
              placeholder="Détails complémentaires…">{{ old('notes', $mouvement?->notes) }}</textarea>
    @error('notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>
