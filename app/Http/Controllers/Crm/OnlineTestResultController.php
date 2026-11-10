<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\OnlineTestAnswer;
use App\Models\OnlineTestInvitation;
use Illuminate\Http\Request;

class OnlineTestResultController extends Controller
{
    public function index(Request $request)
    {
        $results = OnlineTestInvitation::query()
            ->with(['test:id,nom,duree_minutes', 'candidate:id,prenom,nom'])
            ->whereIn('statut', [
                OnlineTestInvitation::STATUT_TERMINE,
                OnlineTestInvitation::STATUT_ANNULE,
                OnlineTestInvitation::STATUT_EXPIRE,
                OnlineTestInvitation::STATUT_EN_COURS,
            ])
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->statut))
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('crm.online-tests.results.index', [
            'results' => $results,
            'statuts' => OnlineTestInvitation::STATUTS,
            'statutActif' => $request->string('statut')->toString(),
        ]);
    }

    public function show(OnlineTestInvitation $invitation)
    {
        $invitation->load([
            'test.questions',
            'answers.question',
            'candidate',
            'validator:id,name',
        ]);

        $answersByQuestion = $invitation->answers->keyBy('online_test_question_id');

        return view('crm.online-tests.results.show', [
            'invitation' => $invitation,
            'answersByQuestion' => $answersByQuestion,
        ]);
    }

    public function grade(Request $request, OnlineTestInvitation $invitation)
    {
        abort_unless($invitation->statut === OnlineTestInvitation::STATUT_TERMINE, 403);

        $invitation->load('test.questions');
        $questionIds = $invitation->test->questions->pluck('id')->all();

        $validated = $request->validate([
            'points' => ['required', 'array'],
            'points.*' => ['nullable', 'numeric', 'min:0'],
            'commentaire_correcteur' => ['nullable', 'string', 'max:5000'],
            'valide' => ['nullable', 'boolean'],
        ]);

        $total = 0;
        foreach ($invitation->test->questions as $question) {
            if (! in_array($question->id, $questionIds, true)) {
                continue;
            }
            $pts = isset($validated['points'][$question->id])
                ? round((float) $validated['points'][$question->id], 2)
                : null;

            if ($pts !== null && $pts > (float) $question->points) {
                $pts = (float) $question->points;
            }

            OnlineTestAnswer::query()->updateOrCreate(
                [
                    'online_test_invitation_id' => $invitation->id,
                    'online_test_question_id' => $question->id,
                ],
                ['points_attribues' => $pts]
            );

            $total += (float) ($pts ?? 0);
        }

        $invitation->update([
            'score_final' => $total,
            'commentaire_correcteur' => $validated['commentaire_correcteur'] ?? null,
            'valide' => $request->boolean('valide'),
            'validated_by' => $request->boolean('valide') ? auth()->id() : null,
            'validated_at' => $request->boolean('valide') ? now() : null,
        ]);

        return redirect()->route('crm.online-tests.results.show', $invitation)
            ->with('success', 'Notes enregistrées'.($request->boolean('valide') ? ' et résultat validé.' : '.'));
    }
}
