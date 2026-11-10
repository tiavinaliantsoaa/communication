<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\OnlineTest;
use App\Models\OnlineTestQuestion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OnlineTestQuestionController extends Controller
{
    public function index(OnlineTest $onlineTest)
    {
        $onlineTest->load('questions');

        return view('crm.online-tests.questions.index', [
            'test' => $onlineTest,
            'types' => OnlineTestQuestion::TYPES,
        ]);
    }

    public function store(Request $request, OnlineTest $onlineTest)
    {
        $validated = $this->validateQuestion($request);

        $onlineTest->questions()->create([
            ...$validated,
            'position' => (int) $onlineTest->questions()->max('position') + 1,
        ]);

        return redirect()->route('crm.online-tests.questions.index', $onlineTest)
            ->with('success', 'Question ajoutée.');
    }

    public function update(Request $request, OnlineTest $onlineTest, OnlineTestQuestion $question)
    {
        abort_unless((int) $question->online_test_id === (int) $onlineTest->id, 404);

        $validated = $this->validateQuestion($request);
        $question->update($validated);

        return redirect()->route('crm.online-tests.questions.index', $onlineTest)
            ->with('success', 'Question mise à jour.');
    }

    public function destroy(OnlineTest $onlineTest, OnlineTestQuestion $question)
    {
        abort_unless((int) $question->online_test_id === (int) $onlineTest->id, 404);
        $question->delete();

        return redirect()->route('crm.online-tests.questions.index', $onlineTest)
            ->with('success', 'Question supprimée.');
    }

    private function validateQuestion(Request $request): array
    {
        $validated = $request->validate([
            'enonce' => ['required', 'string', 'max:5000'],
            'type' => ['required', Rule::in(array_keys(OnlineTestQuestion::TYPES))],
            'points' => ['required', 'numeric', 'min:0', 'max:999'],
            'options_text' => ['nullable', 'string', 'max:5000'],
            'bonne_reponse' => ['nullable', 'string', 'max:255'],
        ], [
            'enonce.required' => 'L’énoncé est obligatoire.',
            'points.required' => 'Indiquez la note de la question.',
        ]);

        $options = [];
        if ($validated['type'] === 'qcm') {
            $options = collect(preg_split('/\r\n|\r|\n/', (string) ($validated['options_text'] ?? '')))
                ->map(fn ($o) => trim($o))
                ->filter()
                ->values()
                ->all();

            if (count($options) < 2) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'options_text' => 'Ajoutez au moins 2 options (une par ligne).',
                ]);
            }
        }

        return [
            'enonce' => $validated['enonce'],
            'type' => $validated['type'],
            'points' => $validated['points'],
            'options' => $validated['type'] === 'qcm' ? $options : null,
            'bonne_reponse' => $validated['type'] === 'qcm'
                ? ($validated['bonne_reponse'] ?: null)
                : null,
        ];
    }
}
