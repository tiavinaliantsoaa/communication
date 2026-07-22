<?php

namespace App\Http\Controllers;

use App\Models\Campagne;
use App\Models\Depense;
use App\Models\Fournisseur;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DepenseController extends Controller
{
    public function index()
    {
        $depenses = Depense::orderByDesc('date_depense')->paginate(15);

        return view('depenses.index', compact('depenses'));
    }

    public function create()
    {
        return view('depenses.create', [
            'fournisseurs' => Fournisseur::orderBy('nom')->pluck('nom'),
            'campagnes' => Campagne::orderBy('nom')->pluck('nom'),
            'statuts' => Depense::statutsForUser(auth()->user()),
            'categories' => Depense::CATEGORIES,
            'canApprove' => auth()->user()?->canApproveDepense() ?? false,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateDepense($request);

        $depense = Depense::create($validated);

        app(ActivityLogger::class)->log(
            'depense',
            auth()->user()->name.' a enregistré la dépense « '.$depense->objet.' » ('.format_ar($depense->montant).')',
            auth()->user(),
            'create',
            'Dépenses',
            route('depenses.index'),
            $depense
        );

        return redirect()->route('depenses.index')->with('success', 'Dépense enregistrée avec succès.');
    }

    public function edit(Depense $depense)
    {
        return view('depenses.edit', [
            'depense' => $depense,
            'fournisseurs' => Fournisseur::orderBy('nom')->pluck('nom'),
            'campagnes' => Campagne::orderBy('nom')->pluck('nom'),
            'statuts' => Depense::statutsForUser(auth()->user()),
            'categories' => Depense::CATEGORIES,
            'canApprove' => auth()->user()?->canApproveDepense() ?? false,
        ]);
    }

    public function update(Request $request, Depense $depense)
    {
        $validated = $this->validateDepense($request, $depense);

        $depense->update($validated);

        app(ActivityLogger::class)->log(
            'depense',
            auth()->user()->name.' a modifié la dépense « '.$depense->objet.' » ('.format_ar($depense->montant).')',
            auth()->user(),
            'update',
            'Dépenses',
            route('depenses.index'),
            $depense
        );

        return redirect()->route('depenses.index')->with('success', 'Dépense mise à jour avec succès.');
    }

    public function destroy(Depense $depense)
    {
        $objet = $depense->objet;
        $montant = $depense->montant;
        $depense->delete();

        app(ActivityLogger::class)->log(
            'depense',
            auth()->user()->name.' a supprimé la dépense « '.$objet.' » ('.format_ar($montant).')',
            auth()->user(),
            'delete',
            'Dépenses',
            route('depenses.index')
        );

        return redirect()->route('depenses.index')->with('success', 'Dépense supprimée avec succès.');
    }

    private function validateDepense(Request $request, ?Depense $depense = null): array
    {
        $user = auth()->user();
        $allowedStatuts = array_keys(Depense::statutsForUser($user));

        // Keep current approved status if a non-super-admin edits without changing it
        if ($depense && $depense->statut === Depense::STATUT_APPROUVE && ! $user?->canApproveDepense()) {
            $allowedStatuts[] = Depense::STATUT_APPROUVE;
        }

        $validated = $request->validate([
            'fournisseur' => ['required', 'string', 'max:255'],
            'objet' => ['required', 'string', 'max:255'],
            'campagne' => ['nullable', 'string', 'max:255'],
            'montant' => ['required', 'numeric', 'min:0'],
            'statut' => ['required', Rule::in($allowedStatuts)],
            'categorie' => ['required', Rule::in(array_keys(Depense::CATEGORIES))],
            'date_depense' => ['required', 'date'],
            'mode_paiement' => ['nullable', Rule::in(array_keys(Depense::MODES_PAIEMENT))],
            'reste_a_payer' => ['nullable', 'numeric', 'min:0'],
        ], [
            'mode_paiement.in' => 'Choisissez Acompte ou Totalité.',
            'reste_a_payer.min' => 'Le reste à payer doit être positif ou nul.',
        ]);

        if ($validated['statut'] === 'paye') {
            if (empty($validated['mode_paiement'])) {
                throw ValidationException::withMessages([
                    'mode_paiement' => 'Sélectionnez Acompte ou Totalité lorsque le statut est Payé.',
                ]);
            }

            if ($validated['mode_paiement'] === 'acompte') {
                if ($validated['reste_a_payer'] === null || $validated['reste_a_payer'] === '') {
                    throw ValidationException::withMessages([
                        'reste_a_payer' => 'Indiquez le reste à payer pour un acompte.',
                    ]);
                }

                if ((float) $validated['reste_a_payer'] > (float) $validated['montant']) {
                    throw ValidationException::withMessages([
                        'reste_a_payer' => 'Le reste à payer ne peut pas dépasser le montant total.',
                    ]);
                }
            } else {
                $validated['reste_a_payer'] = null;
            }
        } else {
            $validated['mode_paiement'] = null;
            $validated['reste_a_payer'] = null;
        }

        if (
            $validated['statut'] === Depense::STATUT_APPROUVE
            && ! $user?->canApproveDepense()
            && (! $depense || $depense->statut !== Depense::STATUT_APPROUVE)
        ) {
            throw ValidationException::withMessages([
                'statut' => 'Seul un Super Admin peut approuver une dépense.',
            ]);
        }

        // Non-super-admin cannot remove approval once set by changing away... 
        // Actually they shouldn't change FROM approved either without being super admin
        if (
            $depense
            && $depense->statut === Depense::STATUT_APPROUVE
            && $validated['statut'] !== Depense::STATUT_APPROUVE
            && ! $user?->canApproveDepense()
        ) {
            throw ValidationException::withMessages([
                'statut' => 'Seul un Super Admin peut modifier le statut Approuvé.',
            ]);
        }

        return $validated;
    }
}
