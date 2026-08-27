<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmCandidate;
use App\Models\CrmCandidateDocument;
use App\Models\CrmDocumentType;
use App\Models\CrmIntake;
use App\Models\CrmInteraction;
use App\Models\CrmProgramme;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\CrmActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CandidateController extends Controller
{
    public function index(Request $request)
    {
        $sort = $request->get('sort', 'newest') === 'oldest' ? 'asc' : 'desc';

        $candidates = CrmCandidate::query()
            ->with('advisor')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->q.'%';
                $concat = DB::getDriverName() === 'sqlite'
                    ? "(prenom || ' ' || nom)"
                    : "CONCAT(prenom, ' ', nom)";
                $q->where(function ($inner) use ($term, $concat) {
                    $inner->where('prenom', 'like', $term)
                        ->orWhere('nom', 'like', $term)
                        ->orWhere('telephone', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhereRaw("{$concat} LIKE ?", [$term]);
                });
            })
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->statut))
            ->when($request->filled('programme'), fn ($q) => $q->where('programme', $request->programme))
            ->when($request->filled('advisor_id'), fn ($q) => $q->where('advisor_id', $request->advisor_id))
            ->orderBy('created_at', $sort)
            ->paginate(15)
            ->withQueryString();

        $advisors = User::orderBy('name')->get(['id', 'name']);
        $programmes = CrmCandidate::query()
            ->whereNotNull('programme')
            ->where('programme', '!=', '')
            ->distinct()
            ->orderBy('programme')
            ->pluck('programme');

        return view('crm.candidats.index', [
            'candidates' => $candidates,
            'advisors' => $advisors,
            'programmes' => $programmes,
            'statuts' => CrmCandidate::STATUTS,
            'filters' => $request->only(['q', 'statut', 'programme', 'advisor_id', 'sort']),
        ]);
    }

    public function create()
    {
        return view('crm.candidats.create', $this->formData());
    }

    public function store(Request $request, CrmActivityLogger $crmLog)
    {
        $validated = $this->validateCandidate($request);
        $this->validateDocuments($request);

        $candidate = DB::transaction(function () use ($validated, $request) {
            $statut = CrmCandidate::resolveStatutFromAttributes($validated);
            $candidate = CrmCandidate::create([
                ...$validated,
                'statut' => $statut,
                'created_by' => auth()->id(),
                'last_interaction_at' => now(),
                'pipeline_order' => (int) CrmCandidate::where('statut', $statut)->max('pipeline_order') + 1,
            ]);

            $this->storeUploadedDocuments($request, $candidate);

            return $candidate;
        });

        $crmLog->created($candidate);
        app(ActivityLogger::class)->log(
            'crm',
            auth()->user()->name.' a créé le candidat « '.$candidate->full_name.' »',
            auth()->user(),
            'create',
            'CRM',
            route('crm.candidats.show', $candidate),
            $candidate
        );

        return redirect()->route('crm.candidats.show', $candidate)
            ->with('success', 'Candidat créé.');
    }

    public function show(CrmCandidate $candidat)
    {
        $candidat->load([
            'advisor',
            'creator',
            'crmNotes.user',
            'activities.user',
            'documents.type',
            'interactions.user',
        ]);

        $documentTypes = CrmDocumentType::active()->ordered()->get();
        $docsByType = $candidat->documents->keyBy('crm_document_type_id');

        $tab = request('tab', 'overview');
        if (! in_array($tab, ['overview', 'notes', 'history', 'documents', 'interactions'], true)) {
            $tab = 'overview';
        }

        return view('crm.candidats.show', [
            'candidate' => $candidat,
            'tab' => $tab,
            'statuts' => CrmCandidate::STATUTS,
            'documentTypes' => $documentTypes,
            'docsByType' => $docsByType,
            'interactionTypes' => CrmInteraction::TYPES,
        ]);
    }

    public function edit(CrmCandidate $candidat)
    {
        if ($candidat->isProfileLocked()) {
            return redirect()->route('crm.candidats.show', $candidat)
                ->with('error', 'Ce candidat est inscrit : la fiche ne peut plus être modifiée.');
        }

        $candidat->load('documents');

        return view('crm.candidats.edit', array_merge($this->formData($candidat), [
            'candidate' => $candidat,
        ]));
    }

    public function update(Request $request, CrmCandidate $candidat, CrmActivityLogger $crmLog)
    {
        if ($candidat->isProfileLocked()) {
            return redirect()->route('crm.candidats.show', $candidat)
                ->with('error', 'Ce candidat est inscrit : la fiche ne peut plus être modifiée.');
        }

        $validated = $this->validateCandidate($request, $candidat);
        $this->validateDocuments($request, $candidat);
        $oldStatut = $candidat->statut;

        DB::transaction(function () use ($validated, $request, $candidat) {
            $statut = CrmCandidate::resolveStatutFromAttributes([
                ...$candidat->getAttributes(),
                ...$validated,
            ]);

            $candidat->update([
                ...$validated,
                'statut' => $statut,
                'last_interaction_at' => now(),
            ]);

            $this->storeUploadedDocuments($request, $candidat);
        });

        $candidat->refresh();

        if ($oldStatut !== $candidat->statut) {
            $crmLog->statusChanged($candidat, $oldStatut, $candidat->statut);
        } else {
            $crmLog->updated($candidat);
        }

        app(ActivityLogger::class)->log(
            'crm',
            auth()->user()->name.' a modifié le candidat « '.$candidat->full_name.' »',
            auth()->user(),
            'update',
            'CRM',
            route('crm.candidats.show', $candidat),
            $candidat
        );

        return redirect()->route('crm.candidats.show', $candidat)
            ->with('success', 'Candidat mis à jour.');
    }

    public function destroy(CrmCandidate $candidat)
    {
        if ($candidat->isProfileLocked()) {
            return redirect()->route('crm.candidats.show', $candidat)
                ->with('error', 'Ce candidat est inscrit : il ne peut plus être supprimé.');
        }

        $name = $candidat->full_name;
        $candidat->load('documents');
        foreach ($candidat->documents as $doc) {
            $doc->delete();
        }
        $candidat->delete();

        app(ActivityLogger::class)->log(
            'crm',
            auth()->user()->name.' a supprimé le candidat « '.$name.' »',
            auth()->user(),
            'delete',
            'CRM',
            route('crm.candidats.index')
        );

        return redirect()->route('crm.candidats.index')
            ->with('success', 'Candidat supprimé.');
    }

    public function destroyDocument(CrmCandidate $candidat, CrmCandidateDocument $document)
    {
        abort_unless((int) $document->crm_candidate_id === (int) $candidat->id, 404);
        abort_unless(auth()->user()?->canAccess('crm.update'), 403);

        $document->delete();
        $candidat->touchInteraction();

        return back()->with('success', 'Document supprimé.');
    }

    public function storeDocuments(Request $request, CrmCandidate $candidat)
    {
        abort_unless(auth()->user()?->canAccess('crm.update'), 403);

        $types = CrmDocumentType::active()->ordered()->get();
        $rules = [];
        foreach ($types as $type) {
            $rules['documents.'.$type->id] = ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg'];
        }
        $request->validate($rules);

        $this->storeUploadedDocuments($request, $candidat);
        $candidat->touchInteraction();

        return redirect()->route('crm.candidats.show', ['candidat' => $candidat, 'tab' => 'documents'])
            ->with('success', 'Documents enregistrés.');
    }

    public function storeNote(Request $request, CrmCandidate $candidat, CrmActivityLogger $crmLog)
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $candidat->crmNotes()->create([
            'user_id' => auth()->id(),
            'content' => $validated['content'],
        ]);

        $candidat->touchInteraction();
        $crmLog->noteAdded($candidat);

        return redirect()->route('crm.candidats.show', ['candidat' => $candidat, 'tab' => 'notes'])
            ->with('success', 'Note ajoutée.');
    }

    public function storeInteraction(Request $request, CrmCandidate $candidat, CrmActivityLogger $crmLog)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys(CrmInteraction::TYPES))],
            'commentaire' => ['required', 'string', 'max:5000'],
        ], [
            'type.required' => 'Le type d’interaction est obligatoire.',
            'commentaire.required' => 'Le commentaire est obligatoire.',
        ]);

        $interaction = $candidat->interactions()->create([
            'user_id' => auth()->id(),
            'type' => $validated['type'],
            'commentaire' => $validated['commentaire'],
        ]);

        $candidat->touchInteraction();
        $crmLog->interactionAdded($candidat, $interaction->type_label);

        return redirect()->route('crm.candidats.show', ['candidat' => $candidat, 'tab' => 'interactions'])
            ->with('success', 'Interaction enregistrée.');
    }

    public function destroyInteraction(CrmCandidate $candidat, CrmInteraction $interaction)
    {
        abort_unless((int) $interaction->crm_candidate_id === (int) $candidat->id, 404);
        abort_unless(auth()->user()?->canAccess('crm.update'), 403);

        $interaction->delete();

        return back()->with('success', 'Interaction supprimée.');
    }

    private function formData(?CrmCandidate $candidate = null): array
    {
        $documentTypes = CrmDocumentType::active()->ordered()->get();
        $existingDocs = $candidate
            ? $candidate->documents()->get()->keyBy('crm_document_type_id')
            : collect();

        return [
            'statuts' => CrmCandidate::STATUTS,
            'sources' => CrmCandidate::SOURCES,
            'genres' => CrmCandidate::GENRES,
            'advisors' => User::orderBy('name')->get(['id', 'name']),
            'intakes' => CrmIntake::optionsForSelect($candidate?->annee_academique),
            'programmes' => CrmProgramme::optionsForSelect($candidate?->programme),
            'documentTypes' => $documentTypes,
            'existingDocs' => $existingDocs,
        ];
    }

    private function validateCandidate(Request $request, ?CrmCandidate $candidate = null): array
    {
        $intakeLabels = CrmIntake::optionsForSelect($candidate?->annee_academique);
        $programmeLabels = CrmProgramme::optionsForSelect($candidate?->programme);

        $validated = $request->validate([
            'prenom' => ['required', 'string', 'max:100'],
            'nom' => ['required', 'string', 'max:100'],
            'genre' => ['nullable', Rule::in(array_keys(CrmCandidate::GENRES))],
            'date_naissance' => ['nullable', 'date'],
            'telephone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'adresse' => ['nullable', 'string', 'max:1000'],
            'contact_parent_1' => ['nullable', 'string', 'max:80'],
            'contact_parent_2' => ['nullable', 'string', 'max:80'],
            'programme' => ['nullable', 'string', 'max:255', Rule::in($programmeLabels)],
            'annee_academique' => ['nullable', 'string', 'max:50', Rule::in($intakeLabels)],
            'niveau_etudes' => ['nullable', 'string', 'max:100'],
            'etablissement_origine' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', Rule::in(array_keys(CrmCandidate::SOURCES))],
            'facebook_profil_url' => ['nullable', 'string', 'max:500'],
            'escm_tour_ville' => ['nullable', 'string', 'max:120'],
            'advisor_id' => ['required', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'paiement_frais_test' => ['sometimes', 'boolean'],
            'validation_test' => ['sometimes', 'boolean'],
            'lettre_admission' => ['sometimes', 'boolean'],
            'paiement_acompte' => ['sometimes', 'boolean'],
            'paiement_totalite' => ['sometimes', 'boolean'],
        ], [
            'prenom.required' => 'Le prénom est obligatoire.',
            'nom.required' => 'Le nom est obligatoire.',
            'email.email' => 'L’adresse e-mail n’est pas valide.',
            'advisor_id.required' => 'Le conseiller assigné est obligatoire.',
            'programme.in' => 'Sélectionnez un programme défini dans les paramètres CRM.',
            'annee_academique.in' => 'Sélectionnez une rentrée définie dans les paramètres CRM.',
        ]);

        $validated['paiement_frais_test'] = $request->boolean('paiement_frais_test');
        $validated['validation_test'] = $request->boolean('validation_test');
        $validated['lettre_admission'] = $request->boolean('lettre_admission');
        $validated['paiement_acompte'] = $request->boolean('paiement_acompte');
        $validated['paiement_totalite'] = $request->boolean('paiement_totalite');

        if (($validated['source'] ?? null) !== 'facebook') {
            $validated['facebook_profil_url'] = null;
        }

        if (($validated['source'] ?? null) !== 'escm_tour') {
            $validated['escm_tour_ville'] = null;
        }

        if (($validated['annee_academique'] ?? '') === '') {
            $validated['annee_academique'] = null;
        }

        if (($validated['programme'] ?? '') === '') {
            $validated['programme'] = null;
        }

        return $validated;
    }

    private function validateDocuments(Request $request, ?CrmCandidate $candidate = null): void
    {
        $types = CrmDocumentType::active()->ordered()->get();
        $rules = [];
        $messages = [];

        foreach ($types as $type) {
            $key = 'documents.'.$type->id;
            $hasExisting = $candidate
                ? $candidate->documents()->where('crm_document_type_id', $type->id)->exists()
                : false;

            $fileRules = ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg'];
            if ($type->is_required && ! $hasExisting) {
                $fileRules[0] = 'required';
                $messages[$key.'.required'] = 'Le document « '.$type->label.' » est obligatoire.';
            }

            $rules[$key] = $fileRules;
        }

        $request->validate($rules, $messages);
    }

    private function storeUploadedDocuments(Request $request, CrmCandidate $candidate): void
    {
        $uploads = $request->file('documents', []);
        if (! is_array($uploads) || $uploads === []) {
            return;
        }

        $allowedIds = CrmDocumentType::active()->pluck('id')->all();

        foreach ($uploads as $typeId => $file) {
            if (! $file || ! in_array((int) $typeId, $allowedIds, true)) {
                continue;
            }

            $path = $file->store('crm/documents/'.$candidate->id, 'public');

            $existing = $candidate->documents()->where('crm_document_type_id', $typeId)->first();
            if ($existing) {
                Storage::disk('public')->delete($existing->path);
                $existing->update([
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'uploaded_by' => auth()->id(),
                ]);
            } else {
                $candidate->documents()->create([
                    'crm_document_type_id' => $typeId,
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'uploaded_by' => auth()->id(),
                ]);
            }
        }
    }
}
