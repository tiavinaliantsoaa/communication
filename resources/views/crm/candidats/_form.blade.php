@php
    /** @var \App\Models\CrmCandidate|null $candidate */
    $isEdit = isset($candidate);
@endphp

<div class="space-y-6">
    <div>
        <h3 class="text-sm font-semibold text-slate-800 mb-3 pb-2 border-b border-slate-100">Informations personnelles</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Prénom <span class="text-red-500">*</span></label>
                <input type="text" name="prenom" value="{{ old('prenom', $candidate->prenom ?? '') }}" required maxlength="100"
                       class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
                @error('prenom')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Nom <span class="text-red-500">*</span></label>
                <input type="text" name="nom" value="{{ old('nom', $candidate->nom ?? '') }}" required maxlength="100"
                       class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
                @error('nom')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Genre</label>
                <select name="genre" class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
                    <option value="">—</option>
                    @foreach($genres as $key => $label)
                        <option value="{{ $key }}" @selected(old('genre', $candidate->genre ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Date de naissance</label>
                <input type="date" name="date_naissance" value="{{ old('date_naissance', isset($candidate) && $candidate->date_naissance ? $candidate->date_naissance->format('Y-m-d') : '') }}"
                       class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
                @error('date_naissance')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Téléphone</label>
                <input type="text" name="telephone" value="{{ old('telephone', $candidate->telephone ?? '') }}" maxlength="40"
                       class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">E-mail</label>
                <input type="email" name="email" value="{{ old('email', $candidate->email ?? '') }}" maxlength="255"
                       class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
                @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Adresse</label>
                <textarea name="adresse" rows="2" class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">{{ old('adresse', $candidate->adresse ?? '') }}</textarea>
            </div>
        </div>
    </div>

    <div>
        <h3 class="text-sm font-semibold text-slate-800 mb-3 pb-2 border-b border-slate-100">Informations académiques</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Programme intéressé</label>
                <input type="text" name="programme" value="{{ old('programme', $candidate->programme ?? '') }}" maxlength="255"
                       class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary"
                       placeholder="Ex. Licence Marketing, Master Finance…">
                @error('programme')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Année / Intake</label>
                <input type="text" name="annee_academique" value="{{ old('annee_academique', $candidate->annee_academique ?? '') }}" maxlength="50"
                       class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary"
                       placeholder="Ex. 2026-2027">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Niveau d’études</label>
                <input type="text" name="niveau_etudes" value="{{ old('niveau_etudes', $candidate->niveau_etudes ?? '') }}" maxlength="100"
                       class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary"
                       placeholder="Ex. Baccalauréat">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Établissement d’origine</label>
                <input type="text" name="etablissement_origine" value="{{ old('etablissement_origine', $candidate->etablissement_origine ?? '') }}" maxlength="255"
                       class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
            </div>
        </div>
    </div>

    <div>
        <h3 class="text-sm font-semibold text-slate-800 mb-3 pb-2 border-b border-slate-100">Informations CRM</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Statut <span class="text-red-500">*</span></label>
                <select name="statut" required class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
                    @foreach($statuts as $key => $label)
                        <option value="{{ $key }}" @selected(old('statut', $candidate->statut ?? 'nouveau') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('statut')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Source</label>
                <select name="source" class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
                    <option value="">—</option>
                    @foreach($sources as $key => $label)
                        <option value="{{ $key }}" @selected(old('source', $candidate->source ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Conseiller assigné</label>
                <select name="advisor_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
                    <option value="">Non assigné</option>
                    @foreach($advisors as $advisor)
                        <option value="{{ $advisor->id }}" @selected((string) old('advisor_id', $candidate->advisor_id ?? '') === (string) $advisor->id)>{{ $advisor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Notes</label>
                <textarea name="notes" rows="3" class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary"
                          placeholder="Notes internes…">{{ old('notes', $candidate->notes ?? '') }}</textarea>
            </div>
        </div>
    </div>
</div>
