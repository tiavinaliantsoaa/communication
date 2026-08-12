<?php

namespace App\Http\Controllers;

use App\Models\Campagne;
use App\Models\Depense;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CampagneController extends Controller
{
    public function index()
    {
        $campagnes = Campagne::with('depense')
            ->orderByDesc('date_debut')
            ->paginate(15);

        return view('campagnes.index', compact('campagnes'));
    }

    public function create()
    {
        return view('campagnes.create', [
            'statuts' => Campagne::STATUTS,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateCampagne($request);

        $campagne = null;
        DB::transaction(function () use ($validated, &$campagne) {
            $depense = null;

            if ((float) $validated['budget'] > 0) {
                $depense = Depense::create($this->depensePayload($validated));
            }

            $campagne = Campagne::create([
                ...$validated,
                'depense_id' => $depense?->id,
            ]);
        });

        app(ActivityLogger::class)->log(
            'campagne',
            auth()->user()->name.' a créé le boost « '.$campagne->nom.' »',
            auth()->user(),
            'create',
            'Campagnes',
            route('campagnes.index'),
            $campagne
        );

        return redirect()->route('campagnes.index')
            ->with('success', 'Boost Facebook créé. Le budget a été déduit du budget mensuel.');
    }

    public function edit(Campagne $campagne)
    {
        return view('campagnes.edit', [
            'campagne' => $campagne,
            'statuts' => Campagne::STATUTS,
        ]);
    }

    public function update(Request $request, Campagne $campagne)
    {
        $validated = $this->validateCampagne($request);
        $campagne->loadMissing('depense');

        DB::transaction(function () use ($validated, $campagne) {
            if ((float) $validated['budget'] > 0) {
                $payload = $this->depensePayload($validated);

                if ($campagne->depense_id && $campagne->depense) {
                    $campagne->depense->update($payload);
                } else {
                    $depense = Depense::create($payload);
                    $validated['depense_id'] = $depense->id;
                }
            } elseif ($campagne->depense) {
                $campagne->depense->delete();
                $validated['depense_id'] = null;
            }

            $campagne->update($validated);
        });

        app(ActivityLogger::class)->log(
            'campagne',
            auth()->user()->name.' a modifié le boost « '.$campagne->nom.' »',
            auth()->user(),
            'update',
            'Campagnes',
            route('campagnes.index'),
            $campagne
        );

        return redirect()->route('campagnes.index')
            ->with('success', 'Boost Facebook mis à jour. Le budget mensuel a été ajusté.');
    }

    public function destroy(Campagne $campagne)
    {
        $nom = $campagne->nom;
        DB::transaction(function () use ($campagne) {
            $depense = $campagne->depense;
            $campagne->delete();
            $depense?->delete();
        });

        app(ActivityLogger::class)->log(
            'campagne',
            auth()->user()->name.' a supprimé le boost « '.$nom.' »',
            auth()->user(),
            'delete',
            'Campagnes',
            route('campagnes.index')
        );

        return redirect()->route('campagnes.index')
            ->with('success', 'Boost Facebook supprimé. Le montant a été rétabli sur le budget mensuel.');
    }

    private function validateCampagne(Request $request): array
    {
        return $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'objectif' => ['nullable', 'string', 'max:255'],
            'budget' => ['required', 'numeric', 'min:0'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'statut' => ['required', Rule::in(array_keys(Campagne::STATUTS))],
        ]);
    }

    private function depensePayload(array $validated): array
    {
        return [
            'fournisseur' => 'Facebook Ads',
            'objet' => 'Boost FB — '.$validated['nom'],
            'campagne' => $validated['nom'],
            'montant' => $validated['budget'],
            'statut' => 'en_attente',
            'categorie' => 'sponsoring_reseaux',
            'date_depense' => $validated['date_debut'],
        ];
    }
}
