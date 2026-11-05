<?php

namespace App\Http\Controllers;

use App\Models\OnlineTestAnswer;
use App\Models\OnlineTestInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OnlineTestTakeController extends Controller
{
    public function show(string $token)
    {
        $invitation = $this->findInvitation($token);
        $invitation->markExpiredIfNeeded();
        $invitation->refresh();

        if ($invitation->statut === OnlineTestInvitation::STATUT_ANNULE) {
            return view('online-tests.take.cancelled', compact('invitation'));
        }

        if (in_array($invitation->statut, [
            OnlineTestInvitation::STATUT_TERMINE,
            OnlineTestInvitation::STATUT_EXPIRE,
        ], true)) {
            return view('online-tests.take.finished', compact('invitation'));
        }

        if ($invitation->statut === OnlineTestInvitation::STATUT_EN_COURS) {
            return redirect()->route('online-tests.take.exam', $token);
        }

        // First visit of unused link: mark opened but still allow start once
        if ($invitation->statut === OnlineTestInvitation::STATUT_EN_ATTENTE && ! $invitation->opened_at) {
            $invitation->update(['opened_at' => now()]);
        }

        $invitation->load('test');

        return view('online-tests.take.notice', compact('invitation'));
    }

    public function start(Request $request, string $token)
    {
        $invitation = $this->findInvitation($token);
        $invitation->markExpiredIfNeeded();
        $invitation->refresh();

        abort_unless($invitation->statut === OnlineTestInvitation::STATUT_EN_ATTENTE, 403);

        $request->validate([
            'accepte_regles' => ['accepted'],
            'fullscreen_ok' => ['accepted'],
        ], [
            'accepte_regles.accepted' => 'Vous devez accepter les règles du test.',
            'fullscreen_ok.accepted' => 'Le mode plein écran est obligatoire pour démarrer.',
        ]);

        $test = $invitation->test;
        abort_unless($test && $test->actif, 403);
        abort_if($test->questions()->count() === 0, 403);

        $startedAt = now();
        $invitation->update([
            'statut' => OnlineTestInvitation::STATUT_EN_COURS,
            'started_at' => $startedAt,
            'ends_at' => $startedAt->copy()->addMinutes((int) $test->duree_minutes),
        ]);

        return redirect()->route('online-tests.take.exam', $token);
    }

    public function exam(string $token)
    {
        $invitation = $this->findInvitation($token);
        $invitation->markExpiredIfNeeded();
        $invitation->refresh();

        if ($invitation->statut === OnlineTestInvitation::STATUT_ANNULE) {
            return view('online-tests.take.cancelled', compact('invitation'));
        }

        if ($invitation->statut === OnlineTestInvitation::STATUT_EXPIRE) {
            return view('online-tests.take.finished', compact('invitation'));
        }

        abort_unless($invitation->statut === OnlineTestInvitation::STATUT_EN_COURS, 403);

        $invitation->load(['test.questions']);

        return view('online-tests.take.exam', [
            'invitation' => $invitation,
            'remainingSeconds' => $invitation->remainingSeconds(),
        ]);
    }

    public function time(string $token)
    {
        $invitation = $this->findInvitation($token);
        $expired = $invitation->markExpiredIfNeeded();
        $invitation->refresh();

        return response()->json([
            'statut' => $invitation->statut,
            'remaining' => $invitation->remainingSeconds(),
            'expired' => $expired || $invitation->statut === OnlineTestInvitation::STATUT_EXPIRE,
            'ends_at' => $invitation->ends_at?->toIso8601String(),
            'server_now' => now()->toIso8601String(),
        ]);
    }

    public function cancel(Request $request, string $token)
    {
        $invitation = $this->findInvitation($token);

        if ($invitation->statut !== OnlineTestInvitation::STATUT_EN_COURS) {
            return redirect()->route('online-tests.take.show', $token);
        }

        $reason = $request->input('reason', 'action_suspecte');
        $labels = [
            'fullscreen_exit' => 'Sortie du mode plein écran',
            'tab_change' => 'Changement d’onglet / perte de focus',
            'visibility' => 'Fenêtre masquée',
            'action_suspecte' => 'Action suspecte détectée',
        ];

        $invitation->update([
            'statut' => OnlineTestInvitation::STATUT_ANNULE,
            'cancelled_at' => now(),
            'cancel_reason' => $labels[$reason] ?? $labels['action_suspecte'],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'redirect' => route('online-tests.take.show', $token),
            ]);
        }

        return redirect()->route('online-tests.take.show', $token);
    }

    public function submit(Request $request, string $token)
    {
        $invitation = $this->findInvitation($token);
        $invitation->markExpiredIfNeeded();
        $invitation->refresh();

        if ($invitation->statut === OnlineTestInvitation::STATUT_EXPIRE) {
            // Auto-submit remaining answers if any, then show finished
            $this->persistAnswers($invitation, $request->input('reponses', []));

            return redirect()->route('online-tests.take.show', $token);
        }

        abort_unless($invitation->statut === OnlineTestInvitation::STATUT_EN_COURS, 403);

        $invitation->load('test.questions');
        $this->persistAnswers($invitation, $request->input('reponses', []));

        $scoreAuto = $this->autoScore($invitation);

        $invitation->update([
            'statut' => OnlineTestInvitation::STATUT_TERMINE,
            'submitted_at' => now(),
            'score_auto' => $scoreAuto,
            'score_final' => $scoreAuto,
        ]);

        return redirect()->route('online-tests.take.show', $token)
            ->with('success', 'Votre test a été soumis.');
    }

    private function findInvitation(string $token): OnlineTestInvitation
    {
        return OnlineTestInvitation::query()
            ->where('token', $token)
            ->with('test')
            ->firstOrFail();
    }

    /**
     * @param  array<string|int, mixed>  $reponses
     */
    private function persistAnswers(OnlineTestInvitation $invitation, array $reponses): void
    {
        $questions = $invitation->test->questions()->get()->keyBy('id');

        DB::transaction(function () use ($invitation, $reponses, $questions) {
            foreach ($reponses as $questionId => $value) {
                $questionId = (int) $questionId;
                if (! $questions->has($questionId)) {
                    continue;
                }

                $reponse = is_array($value) ? json_encode($value) : trim((string) $value);

                OnlineTestAnswer::query()->updateOrCreate(
                    [
                        'online_test_invitation_id' => $invitation->id,
                        'online_test_question_id' => $questionId,
                    ],
                    ['reponse' => $reponse === '' ? null : $reponse]
                );
            }
        });
    }

    private function autoScore(OnlineTestInvitation $invitation): float
    {
        $invitation->load(['answers', 'test.questions']);
        $answers = $invitation->answers->keyBy('online_test_question_id');
        $total = 0.0;

        foreach ($invitation->test->questions as $question) {
            if ($question->type !== 'qcm' || ! $question->bonne_reponse) {
                continue;
            }

            $answer = $answers->get($question->id);
            if (! $answer) {
                continue;
            }

            $ok = trim((string) $answer->reponse) === trim((string) $question->bonne_reponse);
            $pts = $ok ? (float) $question->points : 0;
            $answer->update(['points_attribues' => $pts]);
            $total += $pts;
        }

        return $total;
    }
}
