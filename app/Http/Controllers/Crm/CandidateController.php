<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmCandidate;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\CrmActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $candidate = CrmCandidate::create([
            ...$validated,
            'created_by' => auth()->id(),
            'last_interaction_at' => now(),
            'pipeline_order' => (int) CrmCandidate::where('statut', $validated['statut'])->max('pipeline_order') + 1,
        ]);

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
        ]);

        $tab = request('tab', 'overview');
        if (! in_array($tab, ['overview', 'notes', 'history'], true)) {
            $tab = 'overview';
        }

        return view('crm.candidats.show', [
            'candidate' => $candidat,
            'tab' => $tab,
            'statuts' => CrmCandidate::STATUTS,
        ]);
    }

    public function edit(CrmCandidate $candidat)
    {
        return view('crm.candidats.edit', array_merge($this->formData(), [
            'candidate' => $candidat,
        ]));
    }

    public function update(Request $request, CrmCandidate $candidat, CrmActivityLogger $crmLog)
    {
        $validated = $this->validateCandidate($request);
        $oldStatut = $candidat->statut;

        $candidat->update([
            ...$validated,
            'last_interaction_at' => now(),
        ]);

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
        $name = $candidat->full_name;
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

    private function formData(): array
    {
        return [
            'statuts' => CrmCandidate::STATUTS,
            'sources' => CrmCandidate::SOURCES,
            'genres' => CrmCandidate::GENRES,
            'advisors' => User::orderBy('name')->get(['id', 'name']),
        ];
    }

    private function validateCandidate(Request $request): array
    {
        return $request->validate([
            'prenom' => ['required', 'string', 'max:100'],
            'nom' => ['required', 'string', 'max:100'],
            'genre' => ['nullable', Rule::in(array_keys(CrmCandidate::GENRES))],
            'date_naissance' => ['nullable', 'date'],
            'telephone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'adresse' => ['nullable', 'string', 'max:1000'],
            'programme' => ['nullable', 'string', 'max:255'],
            'annee_academique' => ['nullable', 'string', 'max:50'],
            'niveau_etudes' => ['nullable', 'string', 'max:100'],
            'etablissement_origine' => ['nullable', 'string', 'max:255'],
            'statut' => ['required', Rule::in(array_keys(CrmCandidate::STATUTS))],
            'source' => ['nullable', Rule::in(array_keys(CrmCandidate::SOURCES))],
            'advisor_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ], [
            'prenom.required' => 'Le prénom est obligatoire.',
            'nom.required' => 'Le nom est obligatoire.',
            'email.email' => 'L’adresse e-mail n’est pas valide.',
            'statut.required' => 'Le statut est obligatoire.',
        ]);
    }
}
