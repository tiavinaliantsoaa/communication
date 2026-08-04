@php
    $campagne = $campagne ?? null;
    $statuts = $statuts ?? \App\Models\Campagne::STATUTS;
@endphp

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Nom du boost / publication</label>
    <input type="text" name="nom" value="{{ old('nom', $campagne?->nom) }}" required
           class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm"
           placeholder="Ex. Boost Post MBA — Témoignage alumni">
    @error('nom')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Objectif <span class="text-slate-400 font-normal">(optionnel)</span></label>
    <input type="text" name="objectif" value="{{ old('objectif', $campagne?->objectif) }}"
           class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm"
           placeholder="Ex. Trafic, Notoriété, Messages…">
    @error('objectif')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Budget boost (Ar)</label>
    <input type="number" name="budget" value="{{ old('budget', $campagne?->budget) }}" required min="0" step="1"
           class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
    @error('budget')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    <p class="mt-1.5 text-xs text-slate-500">Ce montant sera automatiquement déduit du budget mensuel correspondant à la date de début.</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Date de début</label>
        <input type="date" name="date_debut" value="{{ old('date_debut', $campagne?->date_debut?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required
               class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
        @error('date_debut')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Date de fin</label>
        <input type="date" name="date_fin" value="{{ old('date_fin', $campagne?->date_fin?->format('Y-m-d')) }}"
               class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
        @error('date_fin')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Statut</label>
    <select name="statut" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
        @foreach($statuts as $value => $label)
            <option value="{{ $value }}" @selected(old('statut', $campagne?->statut ?? 'planifiee') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    @error('statut')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>
