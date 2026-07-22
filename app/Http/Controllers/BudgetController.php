<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\BudgetAnnuel;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BudgetController extends Controller
{
    public function index()
    {
        $budgets = Budget::orderByDesc('annee')->orderByDesc('mois')->paginate(12);

        return view('budgets.index', compact('budgets'));
    }

    public function create()
    {
        return view('budgets.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'montant' => ['required', 'numeric', 'min:0'],
            'annee' => ['required', 'integer', 'min:2020', 'max:2100'],
            'mois' => ['required', 'integer', 'min:1', 'max:12', Rule::unique('budgets')->where(fn ($q) => $q->where('annee', $request->annee))],
        ], [
            'mois.unique' => 'Un budget existe déjà pour ce mois et cette année.',
        ]);

        $this->assertWithinAnnualBudget($validated['annee'], (float) $validated['montant']);

        $budget = Budget::create($validated);

        app(ActivityLogger::class)->log(
            'budget',
            auth()->user()->name.' a créé le budget de '.$budget->mois.'/'.$budget->annee.' ('.format_ar($budget->montant).')',
            auth()->user(),
            'create',
            'Budget mensuel',
            route('budgets.index'),
            $budget
        );

        return redirect()->route('budgets.index')->with('success', 'Budget créé avec succès.');
    }

    public function edit(Budget $budget)
    {
        return view('budgets.edit', compact('budget'));
    }

    public function update(Request $request, Budget $budget)
    {
        $validated = $request->validate([
            'montant' => ['required', 'numeric', 'min:0'],
            'annee' => ['required', 'integer', 'min:2020', 'max:2100'],
            'mois' => ['required', 'integer', 'min:1', 'max:12', Rule::unique('budgets')->where(fn ($q) => $q->where('annee', $request->annee))->ignore($budget->id)],
        ], [
            'mois.unique' => 'Un budget existe déjà pour ce mois et cette année.',
        ]);

        $this->assertWithinAnnualBudget($validated['annee'], (float) $validated['montant'], $budget->id);

        $budget->update($validated);

        app(ActivityLogger::class)->log(
            'budget',
            auth()->user()->name.' a modifié le budget de '.$budget->mois.'/'.$budget->annee.' ('.format_ar($budget->montant).')',
            auth()->user(),
            'update',
            'Budget mensuel',
            route('budgets.index'),
            $budget
        );

        return redirect()->route('budgets.index')->with('success', 'Budget mis à jour avec succès.');
    }

    public function destroy(Budget $budget)
    {
        $label = $budget->mois.'/'.$budget->annee;
        $montant = $budget->montant;
        $budget->delete();

        app(ActivityLogger::class)->log(
            'budget',
            auth()->user()->name.' a supprimé le budget de '.$label.' ('.format_ar($montant).')',
            auth()->user(),
            'delete',
            'Budget mensuel',
            route('budgets.index')
        );

        return redirect()->route('budgets.index')->with('success', 'Budget supprimé avec succès.');
    }

    private function assertWithinAnnualBudget(int $annee, float $montant, ?int $ignoreBudgetId = null): void
    {
        $annual = BudgetAnnuel::where('annee', $annee)->first();

        if (! $annual) {
            return;
        }

        $query = Budget::where('annee', $annee);
        if ($ignoreBudgetId) {
            $query->where('id', '!=', $ignoreBudgetId);
        }

        $alreadyAllocated = (float) $query->sum('montant');
        $remaining = (float) $annual->montant - $alreadyAllocated;

        if ($montant > $remaining + 0.009) {
            throw ValidationException::withMessages([
                'montant' => 'Dépasse le budget annuel restant ('.format_ar(max(0, $remaining)).' sur '.format_ar($annual->montant).').',
            ]);
        }
    }
}
