@php
    $title = 'CRM — Paramètres';
    $subtitle = 'Configuration du module CRM';
@endphp

@extends('layouts.app')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
        <a href="{{ route('crm.dashboard') }}" class="text-xs font-semibold text-escm-primary hover:underline">← Tableau de bord CRM</a>
        <p class="mt-1 text-sm text-slate-500">Ces paramètres concernent uniquement le module CRM.</p>
    </div>
</div>

<div class="max-w-3xl space-y-6">
    @if(session('import_report'))
        @php $report = session('import_report'); @endphp
        <div class="rounded-xl border {{ ($report['dry_run'] ?? false) ? 'border-amber-200 bg-amber-50' : 'border-emerald-200 bg-emerald-50' }} px-5 py-4">
            <p class="text-sm font-semibold {{ ($report['dry_run'] ?? false) ? 'text-amber-900' : 'text-emerald-900' }}">
                {{ ($report['dry_run'] ?? false) ? 'Résultat de la simulation' : 'Résultat de l’import' }}
            </p>
            <ul class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1 text-sm {{ ($report['dry_run'] ?? false) ? 'text-amber-900' : 'text-emerald-900' }}">
                <li>Candidats créés : <strong>{{ $report['candidates_created'] ?? 0 }}</strong></li>
                <li>Candidats mis à jour : <strong>{{ $report['candidates_updated'] ?? 0 }}</strong></li>
                <li>Candidats déjà présents : <strong>{{ $report['candidates_skipped_existing'] ?? 0 }}</strong></li>
                <li>Candidats invalides : <strong>{{ $report['candidates_skipped_invalid'] ?? 0 }}</strong></li>
                <li>Abandons ignorés : <strong>{{ $report['candidates_skipped_abandon'] ?? 0 }}</strong></li>
                <li>Documents liés : <strong>{{ $report['documents_created'] ?? 0 }}</strong></li>
                <li>Documents mis à jour : <strong>{{ $report['documents_updated'] ?? 0 }}</strong></li>
                <li>Documents sans candidat : <strong>{{ $report['documents_skipped_unmatched'] ?? 0 }}</strong></li>
                <li>Programmes ajoutés : <strong>{{ $report['programmes_created'] ?? 0 }}</strong></li>
                <li>Rentrées ajoutées : <strong>{{ $report['intakes_created'] ?? 0 }}</strong></li>
                <li>Types de documents ajoutés : <strong>{{ $report['document_types_created'] ?? 0 }}</strong></li>
            </ul>
            @if(!empty($report['errors']))
                <ul class="mt-3 list-disc list-inside text-xs text-red-700 space-y-0.5">
                    @foreach($report['errors'] as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    {{-- Import Glide --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="text-sm font-semibold text-slate-900">Import depuis Glide</h3>
            <p class="text-xs text-slate-500 mt-0.5">Importez les CSV exportés de l’ancien CRM (candidats + documents). Rien n’est inséré tant que vous ne lancez pas l’import. Utilisez d’abord « Simuler » pour vérifier le mapping.</p>
        </div>

        @if(auth()->user()->canAccess('crm.update'))
        <form method="POST" action="{{ route('crm.settings.import') }}" enctype="multipart/form-data" class="px-5 py-4 space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">CSV candidats <span class="text-slate-400 font-normal">(Data glide.csv)</span></label>
                    <input type="file" name="candidates_csv" accept=".csv,text/csv"
                           class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-escm-primary/10 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-escm-primary hover:file:bg-escm-primary/20">
                    @error('candidates_csv')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">CSV documents <span class="text-slate-400 font-normal">(doc glide.csv)</span></label>
                    <input type="file" name="documents_csv" accept=".csv,text/csv"
                           class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-escm-primary/10 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-escm-primary hover:file:bg-escm-primary/20">
                    @error('documents_csv')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="space-y-2 text-sm text-slate-700">
                <label class="flex items-start gap-2">
                    <input type="hidden" name="create_lookups" value="0">
                    <input type="checkbox" name="create_lookups" value="1" class="mt-0.5 rounded border-slate-300 text-escm-primary focus:ring-escm-primary" checked>
                    <span>Créer automatiquement les programmes, rentrées et types de documents manquants</span>
                </label>
                <label class="flex items-start gap-2">
                    <input type="checkbox" name="skip_abandoned" value="1" class="mt-0.5 rounded border-slate-300 text-escm-primary focus:ring-escm-primary">
                    <span>Ignorer les candidatures abandonnées</span>
                </label>
                <label class="flex items-start gap-2">
                    <input type="checkbox" name="update_existing" value="1" class="mt-0.5 rounded border-slate-300 text-escm-primary focus:ring-escm-primary">
                    <span>Mettre à jour les candidats déjà importés (même identifiant Glide)</span>
                </label>
            </div>
            <p class="text-[11px] text-slate-500">Les documents Glide restent des liens vers les fichiers d’origine (Google Storage) — ils ne sont pas téléchargés sur le serveur. Fichiers jusqu’à 20 Mo. Si l’envoi échoue, augmentez <code>upload_max_filesize</code> et <code>post_max_size</code> côté PHP, ou importez les deux CSV l’un après l’autre.</p>
            <div class="flex flex-wrap items-center gap-2">
                <button type="submit" name="mode" value="dry_run" class="inline-flex items-center bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-medium px-4 py-2 rounded-lg">Simuler</button>
                <button type="submit" name="mode" value="import" class="inline-flex items-center bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg"
                        onclick="return confirm('Lancer l’import Glide maintenant ? Cette opération peut prendre une à deux minutes.')">Importer</button>
            </div>
        </form>
        @else
        <div class="px-5 py-6 text-sm text-slate-500">Vous n’avez pas le droit d’importer des données CRM.</div>
        @endif
    </div>

    {{-- Programmes --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="text-sm font-semibold text-slate-900">Programmes intéressés</h3>
            <p class="text-xs text-slate-500 mt-0.5">Ces valeurs alimentent le menu déroulant « Programme intéressé » du formulaire candidat.</p>
        </div>

        @if(auth()->user()->canAccess('crm.update'))
        <form method="POST" action="{{ route('crm.settings.programmes.store') }}" class="px-5 py-4 border-b border-slate-100 bg-slate-50/70 flex flex-col sm:flex-row gap-3">
            @csrf
            <input type="text" name="label" required maxlength="255" value="{{ old('label') }}"
                   class="flex-1 rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary"
                   placeholder="Ex. Licence Marketing, Master Finance…">
            <button type="submit" class="shrink-0 bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg">Ajouter</button>
        </form>
        @endif

        <div class="divide-y divide-slate-100">
            @forelse($programmes as $programme)
                <div class="px-5 py-3 flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-900">{{ $programme->label }}</p>
                        <p class="text-[11px] {{ $programme->actif ? 'text-emerald-600' : 'text-slate-400' }}">
                            {{ $programme->actif ? 'Actif — visible dans le formulaire' : 'Inactif — masqué du formulaire' }}
                        </p>
                    </div>
                    @if(auth()->user()->canAccess('crm.update'))
                    <div class="flex items-center gap-2 shrink-0">
                        <form method="POST" action="{{ route('crm.settings.programmes.toggle', $programme) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-xs font-semibold px-2.5 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-600">
                                {{ $programme->actif ? 'Désactiver' : 'Activer' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('crm.settings.programmes.destroy', $programme) }}" onsubmit="return confirm('Supprimer ce programme ?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs font-semibold px-2.5 py-1.5 rounded-lg border border-red-200 hover:bg-red-50 text-red-600">Supprimer</button>
                        </form>
                    </div>
                    @endif
                </div>
            @empty
                <div class="px-5 py-10 text-center text-sm text-slate-500">
                    Aucun programme pour le moment. Ajoutez le premier ci-dessus.
                </div>
            @endforelse
        </div>
    </div>

    {{-- Rentrées --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="text-sm font-semibold text-slate-900">Rentrées (Année / Intake)</h3>
            <p class="text-xs text-slate-500 mt-0.5">Ces valeurs alimentent le menu déroulant « Année / Intake » du formulaire candidat.</p>
        </div>

        @if(auth()->user()->canAccess('crm.update'))
        <form method="POST" action="{{ route('crm.settings.intakes.store') }}" class="px-5 py-4 border-b border-slate-100 bg-slate-50/70 flex flex-col sm:flex-row gap-3">
            @csrf
            <input type="text" name="label" required maxlength="100" value="{{ old('label') }}"
                   class="flex-1 rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary"
                   placeholder="Ex. 2026-2027 · Rentrée mars">
            <button type="submit" class="shrink-0 bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg">Ajouter</button>
        </form>
        @endif

        <div class="divide-y divide-slate-100">
            @forelse($intakes as $intake)
                <div class="px-5 py-3 flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-900">{{ $intake->label }}</p>
                        <p class="text-[11px] {{ $intake->actif ? 'text-emerald-600' : 'text-slate-400' }}">
                            {{ $intake->actif ? 'Active — visible dans le formulaire' : 'Inactive — masquée du formulaire' }}
                        </p>
                    </div>
                    @if(auth()->user()->canAccess('crm.update'))
                    <div class="flex items-center gap-2 shrink-0">
                        <form method="POST" action="{{ route('crm.settings.intakes.toggle', $intake) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-xs font-semibold px-2.5 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-600">
                                {{ $intake->actif ? 'Désactiver' : 'Activer' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('crm.settings.intakes.destroy', $intake) }}" onsubmit="return confirm('Supprimer cette rentrée ?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs font-semibold px-2.5 py-1.5 rounded-lg border border-red-200 hover:bg-red-50 text-red-600">Supprimer</button>
                        </form>
                    </div>
                    @endif
                </div>
            @empty
                <div class="px-5 py-10 text-center text-sm text-slate-500">
                    Aucune rentrée pour le moment. Ajoutez la première ci-dessus.
                </div>
            @endforelse
        </div>
    </div>

    {{-- Types de documents --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="text-sm font-semibold text-slate-900">Documents attendus</h3>
            <p class="text-xs text-slate-500 mt-0.5">Ces types de dossiers apparaissent dans la section Documents du formulaire candidat. Cochez « Requis » si le fichier est obligatoire.</p>
        </div>

        @if(auth()->user()->canAccess('crm.update'))
        <form method="POST" action="{{ route('crm.settings.document-types.store') }}" class="px-5 py-4 border-b border-slate-100 bg-slate-50/70 flex flex-col sm:flex-row sm:items-center gap-3">
            @csrf
            <input type="text" name="label" required maxlength="255" value="{{ old('label') }}"
                   class="flex-1 rounded-lg border-slate-300 text-sm focus:border-escm-primary focus:ring-escm-primary"
                   placeholder="Ex. Acte de naissance, Bulletin, CIN…">
            <label class="inline-flex items-center gap-2 text-sm text-slate-700 whitespace-nowrap">
                <input type="checkbox" name="is_required" value="1" class="rounded border-slate-300 text-escm-primary focus:ring-escm-primary"
                       @checked(old('is_required'))>
                Requis
            </label>
            <button type="submit" class="shrink-0 bg-escm-primary hover:bg-escm-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg">Ajouter</button>
        </form>
        @endif

        <div class="divide-y divide-slate-100">
            @forelse($documentTypes as $docType)
                <div class="px-5 py-3 flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-sm font-medium text-slate-900">{{ $docType->label }}</p>
                            @if($docType->is_required)
                                <span class="inline-flex items-center rounded-full bg-amber-50 text-amber-700 text-[10px] font-semibold px-2 py-0.5">Requis</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-100 text-slate-500 text-[10px] font-semibold px-2 py-0.5">Optionnel</span>
                            @endif
                        </div>
                        <p class="text-[11px] {{ $docType->actif ? 'text-emerald-600' : 'text-slate-400' }}">
                            {{ $docType->actif ? 'Actif — visible dans le formulaire' : 'Inactif — masqué du formulaire' }}
                        </p>
                    </div>
                    @if(auth()->user()->canAccess('crm.update'))
                    <div class="flex items-center gap-2 shrink-0 flex-wrap">
                        <form method="POST" action="{{ route('crm.settings.document-types.toggle-required', $docType) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-xs font-semibold px-2.5 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-600">
                                {{ $docType->is_required ? 'Rendre optionnel' : 'Rendre requis' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('crm.settings.document-types.toggle', $docType) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-xs font-semibold px-2.5 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-600">
                                {{ $docType->actif ? 'Désactiver' : 'Activer' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('crm.settings.document-types.destroy', $docType) }}" onsubmit="return confirm('Supprimer ce type de document ? Les fichiers liés seront aussi supprimés.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs font-semibold px-2.5 py-1.5 rounded-lg border border-red-200 hover:bg-red-50 text-red-600">Supprimer</button>
                        </form>
                    </div>
                    @endif
                </div>
            @empty
                <div class="px-5 py-10 text-center text-sm text-slate-500">
                    Aucun type de document. Ajoutez le premier ci-dessus.
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
