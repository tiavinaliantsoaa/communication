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

<div class="max-w-2xl space-y-6">
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
