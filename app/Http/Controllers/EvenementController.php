<?php

namespace App\Http\Controllers;

use App\Models\Depense;
use App\Models\Evenement;
use App\Services\ActivityLogger;
use App\Services\BudgetMensuelService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EvenementController extends Controller
{
    public function index()
    {
        $evenements = Evenement::with('depense')
            ->orderByDesc('date_debut')
            ->paginate(15);

        return view('evenements.index', compact('evenements'));
    }

    public function create()
    {
        return view('evenements.create', [
            'types' => Evenement::TYPES,
            'statuts' => Evenement::STATUTS,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateEvenement($request);

        $evenement = null;
        DB::transaction(function () use ($validated, &$evenement) {
            $depense = null;

            if ((float) $validated['cout'] > 0) {
                $depense = Depense::create($this->depensePayload($validated));
            }

            $evenement = Evenement::create([
                ...$validated,
                'depense_id' => $depense?->id,
            ]);
        });

        app(ActivityLogger::class)->log(
            'evenement',
            auth()->user()->name.' a créé l\'événement « '.$evenement->nom.' »',
            auth()->user(),
            'create',
            'Événements',
            route('evenements.index'),
            $evenement
        );

        $date = Carbon::parse($validated['date_debut']);
        $message = 'Événement créé. Le coût a été déduit du budget mensuel.';
        $snap = app(BudgetMensuelService::class)->forMonth($date->year, $date->month);
        if ($snap['is_depassement']) {
            $message .= ' Attention : dépassement de '.format_ar($snap['depassement']).' reporté sur le mois suivant.';
        }

        return redirect()->route('evenements.index')->with('success', $message);
    }

    public function edit(Evenement $evenement)
    {
        return view('evenements.edit', [
            'evenement' => $evenement,
            'types' => Evenement::TYPES,
            'statuts' => Evenement::STATUTS,
        ]);
    }

    public function update(Request $request, Evenement $evenement)
    {
        $validated = $this->validateEvenement($request);

        DB::transaction(function () use ($validated, $evenement) {
            if ((float) $validated['cout'] > 0) {
                $payload = $this->depensePayload($validated);

                if ($evenement->depense_id && $evenement->depense) {
                    $evenement->depense->update($payload);
                } else {
                    $depense = Depense::create($payload);
                    $validated['depense_id'] = $depense->id;
                }
            } elseif ($evenement->depense) {
                $evenement->depense->delete();
                $validated['depense_id'] = null;
            }

            $evenement->update($validated);
        });

        app(ActivityLogger::class)->log(
            'evenement',
            auth()->user()->name.' a modifié l\'événement « '.$evenement->nom.' »',
            auth()->user(),
            'update',
            'Événements',
            route('evenements.index'),
            $evenement
        );

        $date = Carbon::parse($validated['date_debut']);
        $message = 'Événement mis à jour. Le budget mensuel a été ajusté.';
        $snap = app(BudgetMensuelService::class)->forMonth($date->year, $date->month);
        if ($snap['is_depassement']) {
            $message .= ' Attention : dépassement de '.format_ar($snap['depassement']).' reporté sur le mois suivant.';
        }

        return redirect()->route('evenements.index')->with('success', $message);
    }

    public function destroy(Evenement $evenement)
    {
        $nom = $evenement->nom;
        DB::transaction(function () use ($evenement) {
            $depense = $evenement->depense;
            $evenement->delete();
            $depense?->delete();
        });

        app(ActivityLogger::class)->log(
            'evenement',
            auth()->user()->name.' a supprimé l\'événement « '.$nom.' »',
            auth()->user(),
            'delete',
            'Événements',
            route('evenements.index')
        );

        return redirect()->route('evenements.index')
            ->with('success', 'Événement supprimé. Le montant a été rétabli sur le budget mensuel.');
    }

    private function validateEvenement(Request $request): array
    {
        return $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(Evenement::TYPES))],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'lieu' => ['nullable', 'string', 'max:255'],
            'cout' => ['required', 'numeric', 'min:0'],
            'statut' => ['required', Rule::in(array_keys(Evenement::STATUTS))],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);
    }

    private function depensePayload(array $validated): array
    {
        return [
            'fournisseur' => 'Événement ESCM',
            'objet' => $validated['nom'].' ('.(Evenement::TYPES[$validated['type']] ?? $validated['type']).')',
            'campagne' => null,
            'montant' => $validated['cout'],
            'statut' => 'en_attente',
            'categorie' => 'goodies_evenements',
            'date_depense' => $validated['date_debut'],
        ];
    }
}
