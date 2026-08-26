@php
    /** @var \App\Models\CrmCandidate|null $candidate */
    $isEdit = isset($candidate);
    $currentSource = old('source', $candidate->source ?? '');
@endphp

<div class="space-y-6" x-data="{ source: @js($currentSource) }">
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
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Contact parent 1 <span class="text-slate-400 font-normal">(optionnel)</span></label>
                <input type="text" name="contact_parent_1" value="{{ old('contact_parent_1', $candidate->contact_parent_1 ?? '') }}" maxlength="80"
                       class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary"
                       placeholder="Téléphone ou nom du parent">
                @error('contact_parent_1')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Contact parent 2 <span class="text-slate-400 font-normal">(optionnel)</span></label>
                <input type="text" name="contact_parent_2" value="{{ old('contact_parent_2', $candidate->contact_parent_2 ?? '') }}" maxlength="80"
                       class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary"
                       placeholder="Téléphone ou nom du parent">
                @error('contact_parent_2')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
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
                <select name="programme" class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
                    <option value="">—</option>
                    @foreach($programmes as $programme)
                        <option value="{{ $programme }}" @selected(old('programme', $candidate->programme ?? '') === $programme)>{{ $programme }}</option>
                    @endforeach
                </select>
                @if(empty($programmes))
                    <p class="mt-1.5 text-xs text-amber-700">Aucun programme défini. Ajoutez-en dans <a href="{{ route('crm.settings') }}" class="underline font-medium">Paramètres CRM</a>.</p>
                @endif
                @error('programme')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Année / Intake</label>
                <select name="annee_academique" class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
                    <option value="">—</option>
                    @forelse($intakes as $intake)
                        <option value="{{ $intake }}" @selected(old('annee_academique', $candidate->annee_academique ?? '') === $intake)>{{ $intake }}</option>
                    @empty
                    @endforelse
                </select>
                @if(empty($intakes))
                    <p class="mt-1.5 text-xs text-amber-700">Aucune rentrée définie. Ajoutez-en dans <a href="{{ route('crm.settings') }}" class="underline font-medium">Paramètres CRM</a>.</p>
                @endif
                @error('annee_academique')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
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
                <select name="source" x-model="source" class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
                    <option value="">—</option>
                    @foreach($sources as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2" x-show="source === 'facebook'" x-cloak x-transition>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Lien du profil <span class="text-slate-400 font-normal">(optionnel)</span></label>
                <input type="text" name="facebook_profil_url" value="{{ old('facebook_profil_url', $candidate->facebook_profil_url ?? '') }}" maxlength="500"
                       class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary"
                       placeholder="https://facebook.com/…">
                @error('facebook_profil_url')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2" x-show="source === 'escm_tour'" x-cloak x-transition>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Ville</label>
                <input type="text" name="escm_tour_ville" value="{{ old('escm_tour_ville', $candidate->escm_tour_ville ?? '') }}" maxlength="120"
                       class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary"
                       placeholder="Ville où s’est déroulé l’ESCM Tour">
                @error('escm_tour_ville')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Conseiller assigné <span class="text-red-500">*</span></label>
                <select name="advisor_id" required class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary">
                    <option value="" disabled @selected(! old('advisor_id', $candidate->advisor_id ?? null))>Choisir un conseiller</option>
                    @foreach($advisors as $advisor)
                        <option value="{{ $advisor->id }}" @selected((string) old('advisor_id', $candidate->advisor_id ?? '') === (string) $advisor->id)>{{ $advisor->name }}</option>
                    @endforeach
                </select>
                @error('advisor_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Notes</label>
                <textarea name="notes" rows="3" class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary"
                          placeholder="Notes internes…">{{ old('notes', $candidate->notes ?? '') }}</textarea>
            </div>
        </div>
    </div>

    <div>
        <h3 class="text-sm font-semibold text-slate-800 mb-3 pb-2 border-b border-slate-100">Documents</h3>
        @if(($documentTypes ?? collect())->isEmpty())
            <p class="text-sm text-slate-500">Aucun type de document défini. Configurez-les dans <a href="{{ route('crm.settings') }}" class="text-escm-primary hover:underline font-medium">Paramètres CRM</a>.</p>
        @else
            <div class="space-y-4">
                @foreach($documentTypes as $docType)
                    @php $existing = ($existingDocs ?? collect())->get($docType->id); @endphp
                    <div class="rounded-lg border border-slate-200 p-4">
                        <div class="flex flex-wrap items-center gap-2 mb-2">
                            <p class="text-sm font-medium text-slate-800">{{ $docType->label }}</p>
                            @if($docType->is_required)
                                <span class="inline-flex items-center rounded-full bg-amber-50 text-amber-700 text-[10px] font-semibold px-2 py-0.5">Requis</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-100 text-slate-500 text-[10px] font-semibold px-2 py-0.5">Optionnel</span>
                            @endif
                        </div>
                        @if($existing)
                            <div class="mb-2 flex items-center justify-between gap-3 rounded-md bg-slate-50 px-3 py-2 text-xs text-slate-600">
                                <a href="{{ $existing->url }}" target="_blank" rel="noopener" class="text-escm-primary hover:underline truncate">{{ $existing->original_name ?: 'Fichier déposé' }}</a>
                                <span class="shrink-0 text-slate-400">Déjà joint — remplacez ci-dessous si besoin</span>
                            </div>
                        @endif
                        <input type="file" name="documents[{{ $docType->id }}]"
                               accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                               class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-escm-primary/10 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-escm-primary hover:file:bg-escm-primary/20">
                        @error('documents.'.$docType->id)
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach
            </div>
            <p class="mt-3 text-xs text-slate-500">Formats acceptés : PDF, JPG, PNG, DOC, DOCX — max. 10 Mo par fichier.</p>
        @endif
    </div>
</div>
