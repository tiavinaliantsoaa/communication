<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\OnlineTest;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class OnlineTestController extends Controller
{
    public function index()
    {
        $tests = OnlineTest::query()
            ->withCount('questions')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('crm.online-tests.index', compact('tests'));
    }

    public function create()
    {
        return view('crm.online-tests.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateTest($request);
        $validated['departement_id'] = auth()->user()->currentDepartementId();
        $validated['actif'] = $request->boolean('actif', true);

        $test = OnlineTest::create($validated);

        app(ActivityLogger::class)->log(
            'online_tests',
            auth()->user()->name.' a créé le test « '.$test->nom.' »',
            auth()->user(),
            'create',
            'Test en ligne',
            route('crm.online-tests.show', $test),
            $test
        );

        return redirect()->route('crm.online-tests.questions.index', $test)
            ->with('success', 'Test créé. Ajoutez maintenant les questions.');
    }

    public function show(OnlineTest $onlineTest)
    {
        $onlineTest->load(['questions'])->loadCount('invitations');

        return view('crm.online-tests.show', ['test' => $onlineTest]);
    }

    public function edit(OnlineTest $onlineTest)
    {
        return view('crm.online-tests.edit', ['test' => $onlineTest]);
    }

    public function update(Request $request, OnlineTest $onlineTest)
    {
        $validated = $this->validateTest($request);
        $validated['actif'] = $request->boolean('actif');

        $onlineTest->update($validated);

        app(ActivityLogger::class)->log(
            'online_tests',
            auth()->user()->name.' a modifié le test « '.$onlineTest->nom.' »',
            auth()->user(),
            'update',
            'Test en ligne',
            route('crm.online-tests.show', $onlineTest),
            $onlineTest
        );

        return redirect()->route('crm.online-tests.show', $onlineTest)
            ->with('success', 'Test mis à jour.');
    }

    public function destroy(OnlineTest $onlineTest)
    {
        $nom = $onlineTest->nom;
        $onlineTest->delete();

        app(ActivityLogger::class)->log(
            'online_tests',
            auth()->user()->name.' a supprimé le test « '.$nom.' »',
            auth()->user(),
            'delete',
            'Test en ligne',
            route('crm.online-tests.index')
        );

        return redirect()->route('crm.online-tests.index')
            ->with('success', 'Test supprimé.');
    }

    private function validateTest(Request $request): array
    {
        return $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'duree_minutes' => ['required', 'integer', 'min:1', 'max:600'],
        ], [
            'nom.required' => 'Le nom du test est obligatoire.',
            'duree_minutes.required' => 'La durée est obligatoire.',
            'duree_minutes.min' => 'La durée minimale est de 1 minute.',
        ]);
    }
}
