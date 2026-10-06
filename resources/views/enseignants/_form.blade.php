@php
    $enseignant = $enseignant ?? null;
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Prénom</label>
        <input type="text" name="prenom" value="{{ old('prenom', $enseignant?->prenom) }}" required
               class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
        @error('prenom')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Nom</label>
        <input type="text" name="nom" value="{{ old('nom', $enseignant?->nom) }}" required
               class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
        @error('nom')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Fonction</label>
    <input type="text" name="fonction" value="{{ old('fonction', $enseignant?->fonction) }}" placeholder="Ex. Professeur de marketing"
           class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">E-mail</label>
        <input type="email" name="email" value="{{ old('email', $enseignant?->email) }}"
               class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Téléphone</label>
        <input type="text" name="telephone" value="{{ old('telephone', $enseignant?->telephone) }}"
               class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
    </div>
</div>

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Matières</label>
    <input type="text" name="matieres" value="{{ old('matieres', $enseignant?->matieres) }}" placeholder="Ex. Marketing digital, Communication"
           class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
</div>

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Photo</label>
    <input type="file" name="photo" accept="image/jpeg,image/png,image/webp"
           class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-escm-primary/10 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-escm-primary">
    @error('photo')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Biographie</label>
    <textarea name="biographie" rows="4" class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">{{ old('biographie', $enseignant?->biographie) }}</textarea>
</div>

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Formation</label>
    <textarea name="formation" rows="4" class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">{{ old('formation', $enseignant?->formation) }}</textarea>
</div>

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Expérience</label>
    <textarea name="experience" rows="4" class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">{{ old('experience', $enseignant?->experience) }}</textarea>
</div>
