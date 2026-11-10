<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmCandidate;
use App\Models\OnlineTest;
use App\Models\OnlineTestInvitation;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class OnlineTestLinkController extends Controller
{
    public function index(Request $request)
    {
        $invitations = OnlineTestInvitation::query()
            ->with(['test:id,nom', 'creator:id,name', 'candidate:id,prenom,nom'])
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->statut))
            ->when($request->filled('test'), fn ($q) => $q->where('online_test_id', $request->test))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('crm.online-tests.links.index', [
            'invitations' => $invitations,
            'tests' => OnlineTest::query()->where('actif', true)->orderBy('nom')->get(['id', 'nom']),
            'statuts' => OnlineTestInvitation::STATUTS,
            'statutActif' => $request->string('statut')->toString(),
            'testActif' => $request->string('test')->toString(),
        ]);
    }

    public function create()
    {
        return view('crm.online-tests.links.create', [
            'tests' => OnlineTest::query()
                ->where('actif', true)
                ->withCount('questions')
                ->orderBy('nom')
                ->get(),
            'candidates' => CrmCandidate::query()
                ->where('abandon', false)
                ->orderByDesc('created_at')
                ->limit(300)
                ->get(['id', 'prenom', 'nom', 'email']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'online_test_id' => ['required', 'exists:online_tests,id'],
            'candidat_nom' => ['required', 'string', 'max:255'],
            'candidat_email' => ['nullable', 'email', 'max:255'],
            'crm_candidate_id' => ['nullable', 'exists:crm_candidates,id'],
        ], [
            'online_test_id.required' => 'Choisissez un test.',
            'candidat_nom.required' => 'Le nom du candidat est obligatoire.',
        ]);

        $test = OnlineTest::query()->findOrFail($validated['online_test_id']);
        abort_unless($test->actif, 422, 'Ce test n’est pas actif.');
        abort_if($test->questions()->count() === 0, 422, 'Ajoutez des questions avant de générer un lien.');

        if (! empty($validated['crm_candidate_id'])) {
            $candidate = CrmCandidate::query()->find($validated['crm_candidate_id']);
            if ($candidate) {
                $validated['candidat_nom'] = $candidate->full_name;
                $validated['candidat_email'] = $candidate->email ?: ($validated['candidat_email'] ?? null);
            }
        }

        $invitation = OnlineTestInvitation::create([
            'online_test_id' => $test->id,
            'crm_candidate_id' => $validated['crm_candidate_id'] ?? null,
            'created_by' => auth()->id(),
            'token' => OnlineTestInvitation::generateToken(),
            'candidat_nom' => $validated['candidat_nom'],
            'candidat_email' => $validated['candidat_email'] ?? null,
            'statut' => OnlineTestInvitation::STATUT_EN_ATTENTE,
        ]);

        app(ActivityLogger::class)->log(
            'online_tests',
            auth()->user()->name.' a généré un lien test pour « '.$invitation->candidat_nom.' »',
            auth()->user(),
            'create',
            'Lien test',
            route('crm.online-tests.links.index'),
            $invitation
        );

        return redirect()->route('crm.online-tests.links.index')
            ->with('success', 'Lien unique généré.')
            ->with('lien_cree', $invitation->publicUrl());
    }

    public function destroy(OnlineTestInvitation $invitation)
    {
        abort_unless(in_array($invitation->statut, [
            OnlineTestInvitation::STATUT_EN_ATTENTE,
            OnlineTestInvitation::STATUT_ANNULE,
        ], true), 403);

        $invitation->delete();

        return back()->with('success', 'Lien supprimé.');
    }
}
