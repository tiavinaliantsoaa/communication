@php $test = $test ?? null; @endphp

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Nom <span class="text-red-500">*</span></label>
    <input type="text" name="nom" value="{{ old('nom', $test?->nom) }}" required maxlength="255"
           class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary"
           placeholder="Ex. B1 — QCM de compréhension">
    <p class="mt-1 text-xs text-slate-500">Nom affiché aux administrateurs.</p>
    @error('nom')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Description</label>
    <textarea name="description" rows="4" maxlength="5000"
              class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary"
              placeholder="Consignes ou présentation du test…">{{ old('description', $test?->description) }}</textarea>
    @error('description')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Durée (minutes) <span class="text-red-500">*</span></label>
    <input type="number" name="duree_minutes" value="{{ old('duree_minutes', $test?->duree_minutes ?? 45) }}" required min="1" max="600"
           class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
    <p class="mt-1 text-xs text-slate-500">Minuterie fixe côté serveur : le candidat ne peut pas la réinitialiser.</p>
    @error('duree_minutes')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>

<div>
    <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
        <input type="checkbox" name="actif" value="1" class="rounded border-slate-300 text-escm-primary focus:ring-escm-primary"
               @checked(old('actif', $test?->actif ?? true))>
        <span>Actif</span>
    </label>
</div>
