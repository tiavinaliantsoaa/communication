<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\BudgetAnnuel;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BudgetAnnuelController extends Controller
{
    public function index()
    {
        $budgets = BudgetAnnuel::orderByDesc('annee')->paginate(12);

        $budgets->getCollection()->transform(function (BudgetAnnuel $budget) {
            $budget->alloue = $budget->montantAlloue();
            $budget->restant = $budget->montantRestant();
            $budget->pct = $budget->pourcentageAlloue();

            return $budget;
        });

        return view('budget-annuels.index', compact('budgets'));
    }

    public function create()
    {
        return view('budget-annuels.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'montant' => ['required', 'numeric', 'min:0'],
            'annee' => ['required', 'integer', 'min:2020', 'max:2100', 'unique:budget_annuels,annee'],
        ], [
            'annee.unique' => 'Un budget annuel existe déjà pour cette année.',
        ]);

        $alloue = (float) Budget::where('annee', $validated['annee'])->sum('montant');
        if ($alloue > (float) $validated['montant']) {
            throw ValidationException::withMessages([
                'montant' => 'Le budget annuel ('.format_ar($validated['montant']).') est inférieur aux budgets mensuels déjà alloués ('.format_ar($alloue).').',
            ]);
        }

        $budget = BudgetAnnuel::create($validated);

        app(ActivityLogger::class)->log(
            'budget_annuel',
            auth()->user()->name.' a créé le budget annuel '.$budget->annee.' ('.format_ar($budget->montant).')',
            auth()->user(),
            'create',
            'Budget annuel',
            route('budget-annuels.index'),
            $budget
        );

        return redirect()->route('budget-annuels.index')->with('success', 'Budget annuel créé avec succès.');
    }

    public function edit(BudgetAnnuel $budgetAnnuel)
    {
        return view('budget-annuels.edit', compact('budgetAnnuel'));
    }

    public function update(Request $request, BudgetAnnuel $budgetAnnuel)
    {
        $validated = $request->validate([
            'montant' => ['required', 'numeric', 'min:0'],
            'annee' => ['required', 'integer', 'min:2020', 'max:2100', Rule::unique('budget_annuels', 'annee')->ignore($budgetAnnuel->id)],
        ], [
            'annee.unique' => 'Un budget annuel existe déjà pour cette année.',
        ]);

        $alloue = (float) Budget::where('annee', $validated['annee'])->sum('montant');
        if ($alloue > (float) $validated['montant']) {
            throw ValidationException::withMessages([
                'montant' => 'Impossible : '.format_ar($alloue).' déjà alloués en budgets mensuels. Le budget annuel doit être au moins égal à ce montant.',
            ]);
        }

        $budgetAnnuel->update($validated);

        app(ActivityLogger::class)->log(
            'budget_annuel',
            auth()->user()->name.' a modifié le budget annuel '.$budgetAnnuel->annee.' ('.format_ar($budgetAnnuel->montant).')',
            auth()->user(),
            'update',
            'Budget annuel',
            route('budget-annuels.index'),
            $budgetAnnuel
        );

        return redirect()->route('budget-annuels.index')->with('success', 'Budget annuel mis à jour avec succès.');
    }

    public function destroy(BudgetAnnuel $budgetAnnuel)
    {
        $annee = $budgetAnnuel->annee;
        $montant = $budgetAnnuel->montant;
        $budgetAnnuel->delete();

        app(ActivityLogger::class)->log(
            'budget_annuel',
            auth()->user()->name.' a supprimé le budget annuel '.$annee.' ('.format_ar($montant).')',
            auth()->user(),
            'delete',
            'Budget annuel',
            route('budget-annuels.index')
        );

        return redirect()->route('budget-annuels.index')->with('success', 'Budget annuel supprimé avec succès.');
    }
}
