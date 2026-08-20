@php
    $isEdit = isset($link);
@endphp

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Nom du lien <span class="text-red-500">*</span></label>
    <input type="text" name="nom" value="{{ old('nom', $link->nom ?? '') }}" required maxlength="255"
           class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary"
           placeholder="Ex. Campagne Instagram mars">
    @error('nom')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">URL de destination <span class="text-red-500">*</span></label>
    <input type="url" name="destination_url" value="{{ old('destination_url', $link->destination_url ?? '') }}" required maxlength="2048"
           class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary"
           placeholder="https://exemple.com/page">
    @error('destination_url')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Slug personnalisé <span class="text-slate-400 font-normal">(optionnel)</span></label>
    <div class="flex items-center gap-2">
        <span class="text-xs text-slate-500 whitespace-nowrap font-mono">{{ url('/l') }}/</span>
        <input type="text" name="slug" value="{{ old('slug', $link->slug ?? '') }}" maxlength="100" pattern="[a-zA-Z0-9\-_]*"
               class="flex-1 rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary font-mono"
               placeholder="mon-slug">
    </div>
    <p class="mt-1.5 text-xs text-slate-500">Laissez vide pour générer automatiquement un slug unique.</p>
    @error('slug')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>

@if($isEdit)
<div class="flex items-center gap-3">
    <label class="relative inline-flex items-center cursor-pointer">
        <input type="hidden" name="actif" value="0">
        <input type="checkbox" name="actif" value="1" class="sr-only peer" @checked(old('actif', $link->actif))>
        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-escm-primary/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-escm-primary"></div>
    </label>
    <span class="text-sm text-slate-700">Lien actif (redirige les visiteurs)</span>
</div>
@endif
