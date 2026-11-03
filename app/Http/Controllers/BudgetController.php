<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\BudgetAnnuel;
use App\Models\DepenseCategorie;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BudgetController extends Controller
{
    public function index()
    {
        $budgets = Budget::query()
            ->withCount('lignes')
            ->orderByDesc('annee')
            ->orderByDesc('mois')
            ->paginate(12);

        return view('budgets.index', compact('budgets'));
    }

    public function create()
    {
        return view('budgets.create', $this->formData());
    }

    public function store(Request $request)
    {
        $validated = $this->validateBudget($request);
        $lignes = $this->validatedLignes($request, (float) $validated['montant']);

        $this->assertWithinAnnualBudget($validated['annee'], (float) $validated['montant']);

        $budget = DB::transaction(function () use ($validated, $lignes) {
            $budget = Budget::create($validated);
            $budget->syncLignes($lignes);

            return $budget;
        });

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
        $budget->load('lignes.categorie');

        return view('budgets.edit', array_merge($this->formData($budget), compact('budget')));
    }

    public function update(Request $request, Budget $budget)
    {
        $validated = $this->validateBudget($request, $budget);
        $lignes = $this->validatedLignes($request, (float) $validated['montant']);

        $this->assertWithinAnnualBudget($validated['annee'], (float) $validated['montant'], $budget->id);

        DB::transaction(function () use ($budget, $validated, $lignes) {
            $budget->update($validated);
            $budget->syncLignes($lignes);
        });

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

    private function formData(?Budget $budget = null): array
    {
        $categories = DepenseCategorie::query()
            ->orderBy('position')
            ->orderBy('nom')
            ->get(['id', 'nom', 'slug']);

        $oldLignes = old('lignes');
        if (is_array($oldLignes)) {
            $lignes = collect($oldLignes)
                ->filter(fn ($row) => filled($row['depense_categorie_id'] ?? null) || filled($row['montant'] ?? null))
                ->values()
                ->map(fn ($row) => [
                    'depense_categorie_id' => (string) ($row['depense_categorie_id'] ?? ''),
                    'montant' => (string) ($row['montant'] ?? ''),
                ])
                ->all();
        } elseif ($budget) {
            $lignes = $budget->lignes->map(fn ($ligne) => [
                'depense_categorie_id' => (string) $ligne->depense_categorie_id,
                'montant' => (string) $ligne->montant,
            ])->all();
        } else {
            $lignes = [];
        }

        if ($lignes === []) {
            $lignes = [['depense_categorie_id' => '', 'montant' => '']];
        }

        return compact('categories', 'lignes');
    }

    private function validateBudget(Request $request, ?Budget $budget = null): array
    {
        return $request->validate([
            'montant' => ['required', 'numeric', 'min:0'],
            'annee' => ['required', 'integer', 'min:2020', 'max:2100'],
            'mois' => ['required', 'integer', 'min:1', 'max:12', $this->uniqueMonthRule($request, $budget?->id)],
        ], [
            'mois.unique' => 'Un budget existe déjà pour ce mois et cette année.',
        ]);
    }

    /**
     * @return list<array{depense_categorie_id: int, montant: float}>
     */
    private function validatedLignes(Request $request, float $montantBudget): array
    {
        $allowedIds = DepenseCategorie::query()->pluck('id')->map(fn ($id) => (int) $id)->all();

        $validated = $request->validate([
            'lignes' => ['nullable', 'array'],
            'lignes.*.depense_categorie_id' => ['nullable', 'integer', Rule::in($allowedIds)],
            'lignes.*.montant' => ['nullable', 'numeric', 'min:0'],
        ], [
            'lignes.*.depense_categorie_id.in' => 'Catégorie invalide.',
        ]);

        $rows = collect($validated['lignes'] ?? [])
            ->filter(fn ($row) => filled($row['depense_categorie_id'] ?? null))
            ->map(fn ($row) => [
                'depense_categorie_id' => (int) $row['depense_categorie_id'],
                'montant' => round((float) ($row['montant'] ?? 0), 2),
            ])
            ->values();

        if ($rows->isEmpty()) {
            return [];
        }

        if ($rows->pluck('depense_categorie_id')->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages([
                'lignes' => 'Chaque catégorie ne peut apparaître qu’une seule fois dans la répartition.',
            ]);
        }

        $sum = round((float) $rows->sum('montant'), 2);
        if ($sum - $montantBudget > 0.009) {
            throw ValidationException::withMessages([
                'lignes' => 'La somme des catégories ('.format_ar($sum).') dépasse le budget mensuel ('.format_ar($montantBudget).').',
            ]);
        }

        return $rows->all();
    }

    private function uniqueMonthRule(Request $request, ?int $ignoreId = null)
    {
        $rule = Rule::unique('budgets')->where(function ($query) use ($request) {
            $query->where('annee', $request->annee);
            $user = $request->user();
            $departementId = $user?->currentDepartementId();
            if ($user && $user->restrictsDepartementData() && $departementId) {
                $query->where('departement_id', $departementId);
            }
        });

        if ($ignoreId) {
            $rule->ignore($ignoreId);
        }

        return $rule;
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
