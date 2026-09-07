@php
    $title = $candidate->full_name;
    $subtitle = 'Fiche candidat CRM';
@endphp

@extends('layouts.app')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
    <div>
        <div class="flex items-center gap-3 flex-wrap">
            <h2 class="text-xl font-semibold text-slate-900">{{ $candidate->full_name }}</h2>
            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $candidate->statut_color }}">{{ $candidate->statut_label }}</span>
            @if($candidate->abandon)
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-red-50 text-red-700">Abandon</span>
            @endif
        </div>
        <p class="mt-1 text-sm text-slate-500">
            {{ $candidate->programme ?: 'Programme non renseigné' }}
            @if($candidate->advisor)
                · Conseiller : {{ $candidate->advisor->name }}
            @endif
        </p>
    </div>
    <div class="flex items-center gap-2 shrink-0 flex-wrap justify-end" x-data="{ abandonOpen: {{ $errors->has('abandon_raison') ? 'true' : 'false' }} }">
        <a href="{{ route($candidate->abandon ? 'crm.abandons' : 'crm.candidats.index') }}" class="text-sm text-slate-600 hover:text-slate-900 px-3 py-2">Retour</a>
        @if($candidate->isProfileLocked())
            <span class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-800 text-xs font-medium px-3 py-2">
                Fiche verrouillée (inscrit)
            </span>
        @endif
        @if(auth()->user()->canAccess('crm.update') && $candidate->canEditProfile())
            <a href="{{ route('crm.candidats.edit', $candidate) }}" class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-medium px-4 py-2 rounded-lg">Modifier</a>
        @endif
        @if(auth()->user()->canAccess('crm.update') && ! $candidate->abandon)
            <button type="button" @click="abandonOpen = true" class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium px-4 py-2 rounded-lg">Abandon</button>
        @endif
        @if(auth()->user()->canAccess('crm.delete') && ! $candidate->isProfileLocked())
            <form action="{{ route('crm.candidats.destroy', $candidate) }}" method="POST" onsubmit="return confirm('Supprimer ce candidat et tout son historique ?')">
                @csrf @method('DELETE')
                <button type="submit" class="inline-flex items-center gap-2 bg-white border border-red-200 hover:bg-red-50 text-red-700 text-sm font-medium px-4 py-2 rounded-lg">Supprimer</button>
            </form>
        @endif

        @if(auth()->user()->canAccess('crm.update') && ! $candidate->abandon)
        <div x-show="abandonOpen" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-slate-900/60" @keydown.escape.window="abandonOpen = false">
            <div class="absolute inset-0" @click="abandonOpen = false"></div>
            <div class="relative w-full max-w-md bg-white rounded-xl shadow-2xl p-5" @click.stop>
                <h3 class="text-base font-semibold text-slate-900">Marquer comme abandon</h3>
                <p class="mt-1 text-sm text-slate-500">Le candidat sera déplacé dans la colonne Abandon du pipeline. Indiquez le motif.</p>
                <form action="{{ route('crm.candidats.abandon', $candidate) }}" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label for="abandon_raison" class="block text-sm font-medium text-slate-700 mb-1.5">Motif de l’abandon <span class="text-red-500">*</span></label>
                        <textarea id="abandon_raison" name="abandon_raison" rows="3" required maxlength="255"
                                  class="w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500"
                                  placeholder="Ex. Choix d’une autre école, plus de réponse…">{{ old('abandon_raison') }}</textarea>
                        @error('abandon_raison')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex items-center justify-end gap-2">
                        <button type="button" @click="abandonOpen = false" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900">Annuler</button>
                        <button type="submit" class="inline-flex items-center bg-red-600 hover:bg-red-700 text-white text-sm font-medium px-4 py-2 rounded-lg">Confirmer l’abandon</button>
                    </div>
                </form>
            </div>
        </div>
        @endif
    </div>
</div>

@if($candidate->abandon)
    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        Candidature abandonnée{{ $candidate->abandon_raison ? ' — '.$candidate->abandon_raison : '' }}.
        Elle apparaît dans la colonne Abandon du pipeline.
    </div>
@endif

<div class="border-b border-slate-200 mb-6">
    <nav class="flex gap-1 -mb-px overflow-x-auto">
        @foreach([
            'overview' => 'Vue d’ensemble',
            'documents' => 'Documents',
            'notes' => 'Notes',
            'interactions' => 'Interactions',
            'history' => 'Historique',
        ] as $key => $label)
            <a href="{{ route('crm.candidats.show', ['candidat' => $candidate, 'tab' => $key]) }}"
               class="px-4 py-2.5 text-sm font-medium whitespace-nowrap border-b-2 transition-colors {{ $tab === $key ? 'border-escm-primary text-escm-primary' : 'border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300' }}">
                {{ $label }}
            </a>
        @endforeach
    </nav>
</div>

@if($tab === 'overview')
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <h3 class="text-sm font-semibold text-slate-800 mb-4">Informations personnelles</h3>
        <dl class="space-y-3 text-sm">
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Genre</dt><dd class="text-slate-900 font-medium">{{ $candidate->genre_label }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Date de naissance</dt><dd class="text-slate-900 font-medium">{{ format_date($candidate->date_naissance) }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Téléphone</dt><dd class="text-slate-900 font-medium">{{ $candidate->telephone ?: '—' }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-slate-500">E-mail</dt><dd class="text-slate-900 font-medium break-all">{{ $candidate->email ?: '—' }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Contact parent 1</dt><dd class="text-slate-900 font-medium">{{ $candidate->contact_parent_1 ?: '—' }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Contact parent 2</dt><dd class="text-slate-900 font-medium">{{ $candidate->contact_parent_2 ?: '—' }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Adresse</dt><dd class="text-slate-900 font-medium text-right">{{ $candidate->adresse ?: '—' }}</dd></div>
        </dl>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <h3 class="text-sm font-semibold text-slate-800 mb-4">Informations académiques</h3>
        <dl class="space-y-3 text-sm">
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Programme intéressé</dt><dd class="text-slate-900 font-medium">{{ $candidate->programme ?: '—' }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Année / Intake</dt><dd class="text-slate-900 font-medium">{{ $candidate->annee_academique ?: '—' }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Niveau d’études</dt><dd class="text-slate-900 font-medium">{{ $candidate->niveau_etudes ?: '—' }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Établissement</dt><dd class="text-slate-900 font-medium text-right">{{ $candidate->etablissement_origine ?: '—' }}</dd></div>
        </dl>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 lg:col-span-2">
        <h3 class="text-sm font-semibold text-slate-800 mb-4">Statut CRM</h3>
        <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm mb-4">
            <div><dt class="text-slate-500 mb-1">Statut actuel</dt><dd><span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $candidate->statut_color }}">{{ $candidate->statut_label }}</span></dd></div>
            <div><dt class="text-slate-500 mb-1">Source</dt><dd class="text-slate-900 font-medium">{{ $candidate->source_label }}</dd></div>
            <div><dt class="text-slate-500 mb-1">Conseiller</dt><dd class="text-slate-900 font-medium">{{ $candidate->advisor?->name ?: 'Non assigné' }}</dd></div>
            @if($candidate->source === 'facebook' && $candidate->facebook_profil_url)
            <div class="sm:col-span-3"><dt class="text-slate-500 mb-1">Lien du profil Facebook</dt><dd class="text-slate-900 font-medium break-all"><a href="{{ $candidate->facebook_profil_url }}" target="_blank" rel="noopener" class="text-escm-primary hover:underline">{{ $candidate->facebook_profil_url }}</a></dd></div>
            @endif
            @if($candidate->source === 'escm_tour' && $candidate->escm_tour_ville)
            <div class="sm:col-span-3"><dt class="text-slate-500 mb-1">Ville ESCM Tour</dt><dd class="text-slate-900 font-medium">{{ $candidate->escm_tour_ville }}</dd></div>
            @endif
        </dl>
        <div class="pt-4 border-t border-slate-100">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-3">Conditions d’avancement</p>
            <ul class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                <li class="flex items-center gap-2 {{ $candidate->programme ? 'text-emerald-700' : 'text-slate-400' }}">
                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full {{ $candidate->programme ? 'bg-emerald-100' : 'bg-slate-100' }} text-[10px] font-bold">{{ $candidate->programme ? '✓' : '—' }}</span>
                    Choix du programme
                    <span class="text-[11px] text-slate-400">→ Intention déposée</span>
                </li>
                <li class="flex items-center gap-2 {{ $candidate->validation_test ? 'text-emerald-700' : 'text-slate-400' }}">
                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full {{ $candidate->validation_test ? 'bg-emerald-100' : 'bg-slate-100' }} text-[10px] font-bold">{{ $candidate->validation_test ? '✓' : '—' }}</span>
                    Test effectué
                    <span class="text-[11px] text-slate-400">→ Évaluation</span>
                </li>
                <li class="flex items-center gap-2 {{ ($candidate->paiement_acompte || $candidate->paiement_totalite) ? 'text-emerald-700' : 'text-slate-400' }}">
                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full {{ ($candidate->paiement_acompte || $candidate->paiement_totalite) ? 'bg-emerald-100' : 'bg-slate-100' }} text-[10px] font-bold">{{ ($candidate->paiement_acompte || $candidate->paiement_totalite) ? '✓' : '—' }}</span>
                    Paiement acompte / totalité
                    <span class="text-[11px] text-slate-400">→ Inscrit</span>
                </li>
            </ul>
        </div>
        @if($candidate->notes)
            <div class="mt-4 pt-4 border-t border-slate-100">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">Notes internes</p>
                <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ $candidate->notes }}</p>
            </div>
        @endif
    </div>
</div>
@endif

@if($tab === 'documents')
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
        <h3 class="text-sm font-semibold text-slate-800">Documents du dossier</h3>
        @if($candidate->isProfileLocked() && ! $candidate->canEditProfile())
            <span class="text-[11px] text-slate-500">Ajout / remplacement possible — fiche profil verrouillée</span>
        @endif
    </div>
    <div class="divide-y divide-slate-100">
        @forelse($documentTypes as $docType)
            @php $docs = $docsByType->get($docType->id, collect()); @endphp
            <div class="px-5 py-4 space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-start gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-sm font-medium text-slate-900">{{ $docType->label }}</p>
                            @if($docType->is_required)
                                <span class="inline-flex items-center rounded-full bg-amber-50 text-amber-700 text-[10px] font-semibold px-2 py-0.5">Requis</span>
                            @endif
                        </div>
                        @forelse($docs as $doc)
                            <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
                                <div class="min-w-0">
                                    @if($doc->url)
                                        <a href="{{ $doc->url }}" target="_blank" rel="noopener" class="inline-block text-sm text-escm-primary hover:underline truncate max-w-full">{{ $doc->original_name ?: 'Voir le fichier' }}</a>
                                    @else
                                        <span class="text-sm text-slate-600">{{ $doc->original_name ?: 'Fichier' }}</span>
                                    @endif
                                    <p class="text-[11px] text-slate-400 mt-0.5">
                                        Déposé le {{ $doc->updated_at?->format('d/m/Y H:i') }}
                                        @if($doc->isExternal())
                                            · Lien Glide
                                        @endif
                                    </p>
                                </div>
                                @if(auth()->user()->canAccess('crm.update'))
                                <form method="POST" action="{{ route('crm.candidats.documents.destroy', [$candidate, $doc]) }}" onsubmit="return confirm('Supprimer ce document ?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-red-600 hover:underline">Supprimer</button>
                                </form>
                                @endif
                            </div>
                        @empty
                            <p class="mt-1 text-sm text-slate-400">Non fourni</p>
                        @endforelse
                    </div>
                </div>
            </div>
        @empty
            <div class="px-5 py-10 text-center text-sm text-slate-500">
                Aucun type de document défini. Configurez-les dans <a href="{{ route('crm.settings') }}" class="text-escm-primary hover:underline">Paramètres CRM</a>.
            </div>
        @endforelse
    </div>
    @if(auth()->user()->canAccess('crm.update') && $documentTypes->isNotEmpty())
    <form method="POST" action="{{ route('crm.candidats.documents.store', $candidate) }}" enctype="multipart/form-data" class="border-t border-slate-100 px-5 py-4 space-y-4 bg-slate-50/40">
        @csrf
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Ajouter / remplacer des fichiers</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach($documentTypes as $docType)
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">{{ $docType->label }}</label>
                    <input type="file" name="documents[{{ $docType->id }}]"
                           accept=".pdf,.jpg,.jpeg,application/pdf,image/jpeg"
                           class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-escm-primary/10 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-escm-primary hover:file:bg-escm-primary/20">
                    @error('documents.'.$docType->id)
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach
        </div>
        <button type="submit" class="inline-flex items-center gap-2 bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-4 py-2.5 rounded-lg">Enregistrer les documents</button>
    </form>
    @endif
</div>
@endif

@if($tab === 'notes')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1">
        @if(auth()->user()->canAccess('crm.update'))
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 sticky top-4">
            <h3 class="text-sm font-semibold text-slate-800 mb-3">Ajouter une note</h3>
            <form action="{{ route('crm.candidats.notes.store', $candidate) }}" method="POST" class="space-y-3">
                @csrf
                <textarea name="content" rows="4" required maxlength="5000"
                          class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary"
                          placeholder="Écrire une note…">{{ old('content') }}</textarea>
                @error('content')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                <button type="submit" class="w-full bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-4 py-2.5 rounded-lg">Ajouter</button>
            </form>
        </div>
        @endif
    </div>
    <div class="lg:col-span-2 space-y-3">
        @forelse($candidate->crmNotes as $note)
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
            <div class="flex items-center justify-between gap-3 mb-2">
                <p class="text-sm font-medium text-slate-900">{{ $note->user?->name ?? 'Utilisateur' }}</p>
                <p class="text-xs text-slate-500">{{ $note->created_at?->format('d/m/Y H:i') }}</p>
            </div>
            <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ $note->content }}</p>
        </div>
        @empty
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-5 py-10 text-center text-slate-500 text-sm">
            Aucune note pour ce candidat.
        </div>
        @endforelse
    </div>
</div>
@endif

@if($tab === 'interactions')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1">
        @if(auth()->user()->canAccess('crm.update'))
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 sticky top-4">
            <h3 class="text-sm font-semibold text-slate-800 mb-3">Nouvelle interaction</h3>
            <form action="{{ route('crm.candidats.interactions.store', $candidate) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Canal</label>
                    <div class="grid grid-cols-1 gap-2">
                        @foreach($interactionTypes as $key => $label)
                            <label class="flex items-center gap-3 rounded-lg border border-slate-200 px-3 py-2.5 cursor-pointer hover:bg-slate-50 has-[:checked]:border-escm-primary has-[:checked]:bg-escm-primary/5">
                                <input type="radio" name="type" value="{{ $key }}" class="text-escm-primary focus:ring-escm-primary"
                                       @checked(old('type', 'appel') === $key) required>
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full
                                    {{ $key === 'reseaux_sociaux' ? 'bg-violet-50 text-violet-600' : '' }}
                                    {{ $key === 'appel' ? 'bg-sky-50 text-sky-600' : '' }}
                                    {{ $key === 'sms' ? 'bg-amber-50 text-amber-600' : '' }}
                                    {{ $key === 'whatsapp' ? 'bg-emerald-50 text-emerald-600' : '' }}
                                    {{ $key === 'mail' ? 'bg-blue-50 text-blue-600' : '' }}
                                ">
                                    @if($key === 'reseaux_sociaux')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/></svg>
                                    @elseif($key === 'appel')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    @elseif($key === 'sms')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                                    @elseif($key === 'whatsapp')
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    @endif
                                </span>
                                <span class="text-sm font-medium text-slate-700">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('type')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Commentaire</label>
                    <textarea name="commentaire" rows="4" required maxlength="5000"
                              class="w-full rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary"
                              placeholder="Résumé de l’échange…">{{ old('commentaire') }}</textarea>
                    @error('commentaire')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="w-full bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-4 py-2.5 rounded-lg">Enregistrer</button>
            </form>
        </div>
        @endif
    </div>
    <div class="lg:col-span-2 space-y-3">
        @forelse($candidate->interactions as $interaction)
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
            <div class="flex items-start justify-between gap-3 mb-2">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $interaction->type_color }}">
                        @if($interaction->type === 'reseaux_sociaux')
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/></svg>
                        @elseif($interaction->type === 'appel')
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        @elseif($interaction->type === 'sms')
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                        @elseif($interaction->type === 'whatsapp')
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        @endif
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-900">{{ $interaction->type_label }}</p>
                        <p class="text-xs text-slate-500">{{ $interaction->user?->name ?? 'Utilisateur' }} · {{ $interaction->created_at?->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
                @if(auth()->user()->canAccess('crm.update'))
                <form method="POST" action="{{ route('crm.candidats.interactions.destroy', [$candidate, $interaction]) }}" onsubmit="return confirm('Supprimer cette interaction ?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-xs text-slate-400 hover:text-red-600">Suppr.</button>
                </form>
                @endif
            </div>
            <p class="text-sm text-slate-700 whitespace-pre-wrap pl-12">{{ $interaction->commentaire }}</p>
        </div>
        @empty
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-5 py-10 text-center text-slate-500 text-sm">
            Aucune interaction enregistrée pour ce candidat.
        </div>
        @endforelse
    </div>
</div>
@endif

@if($tab === 'history')
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100">
        <h3 class="text-sm font-semibold text-slate-800">Historique des activités</h3>
    </div>
    <ul class="divide-y divide-slate-100">
        @forelse($candidate->activities as $activity)
        <li class="px-5 py-4 flex gap-4">
            <div class="mt-1 shrink-0">
                @php
                    $iconClass = match($activity->type) {
                        'created' => 'bg-blue-50 text-blue-600',
                        'status_changed' => 'bg-violet-50 text-violet-600',
                        'note_added' => 'bg-amber-50 text-amber-600',
                        'interaction_added' => 'bg-emerald-50 text-emerald-600',
                        'abandoned' => 'bg-red-50 text-red-600',
                        default => 'bg-slate-100 text-slate-600',
                    };
                @endphp
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full {{ $iconClass }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <p class="text-sm font-medium text-slate-900">{{ $activity->title }}</p>
                    <p class="text-xs text-slate-500">{{ $activity->created_at?->format('d/m/Y H:i') }}</p>
                </div>
                @if($activity->description)
                    <p class="mt-0.5 text-sm text-slate-600">{{ $activity->description }}</p>
                @endif
                <p class="mt-1 text-xs text-slate-400">par {{ $activity->user?->name ?? 'Système' }}</p>
            </div>
        </li>
        @empty
        <li class="px-5 py-10 text-center text-slate-500 text-sm">Aucune activité enregistrée.</li>
        @endforelse
    </ul>
</div>
@endif
@endsection
