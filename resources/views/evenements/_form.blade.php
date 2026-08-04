@php $evenement = $evenement ?? null; @endphp

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Nom de l'événement</label>
    <input type="text" name="nom" value="{{ old('nom', $evenement?->nom) }}" required
           class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm"
           placeholder="Ex. Salon étudiant Antananarivo">
    @error('nom')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Type</label>
        <select name="type" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
            @foreach($types as $value => $label)
                <option value="{{ $value }}" @selected(old('type', $evenement?->type) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Statut</label>
        <select name="statut" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
            @foreach($statuts as $value => $label)
                <option value="{{ $value }}" @selected(old('statut', $evenement?->statut ?? 'planifie') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('statut')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Date de début</label>
        <input type="date" name="date_debut" value="{{ old('date_debut', $evenement?->date_debut?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required
               class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
        @error('date_debut')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Date de fin <span class="text-slate-400 font-normal">(optionnel)</span></label>
        <input type="date" name="date_fin" value="{{ old('date_fin', $evenement?->date_fin?->format('Y-m-d')) }}"
               class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
        @error('date_fin')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Lieu</label>
    <input type="text" name="lieu" value="{{ old('lieu', $evenement?->lieu) }}"
           class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm"
           placeholder="Ex. CCI Ivato, Campus ESCM…">
    @error('lieu')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Coût (Ar)</label>
    <input type="number" name="cout" value="{{ old('cout', $evenement?->cout ?? 0) }}" required min="0" step="1"
           class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm">
    @error('cout')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    <p class="mt-1.5 text-xs text-slate-500">
        Ce montant sera déduit du budget mensuel de la date de début.
        Un dépassement reste autorisé : le surplus est automatiquement reporté sur le budget du mois suivant.
    </p>
</div>

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Description <span class="text-slate-400 font-normal">(optionnel)</span></label>
    <textarea name="description" rows="3"
              class="w-full rounded-lg border-slate-300 shadow-sm focus:border-escm-primary focus:ring-escm-primary text-sm"
              placeholder="Détails, stand, partenaires…">{{ old('description', $evenement?->description) }}</textarea>
    @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>
