<?php

namespace App\Http\Controllers;

use App\Models\Departement;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartementController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:100', 'unique:departements,nom'],
        ], [
            'nom.required' => 'Le nom du département est obligatoire.',
            'nom.unique' => 'Ce département existe déjà.',
        ]);

        $nom = trim($data['nom']);
        $departement = Departement::create([
            'nom' => $nom,
            'slug' => Departement::makeSlug($nom),
            'is_system' => false,
        ]);

        app(ActivityLogger::class)->log(
            'user',
            $request->user()->name.' a créé le département « '.$departement->nom.' »',
            $request->user(),
            'create',
            'Départements',
            route('users.index'),
            $departement
        );

        return redirect()->route('users.index')->with('success', 'Département créé.');
    }

    public function update(Request $request, Departement $departement)
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:100', Rule::unique('departements', 'nom')->ignore($departement->id)],
        ], [
            'nom.required' => 'Le nom du département est obligatoire.',
            'nom.unique' => 'Ce département existe déjà.',
        ]);

        $departement->update(['nom' => trim($data['nom'])]);

        app(ActivityLogger::class)->log(
            'user',
            $request->user()->name.' a renommé un département en « '.$departement->nom.' »',
            $request->user(),
            'update',
            'Départements',
            route('users.index'),
            $departement
        );

        return redirect()->route('users.index')->with('success', 'Département mis à jour.');
    }

    public function destroy(Request $request, Departement $departement)
    {
        if ($departement->is_system) {
            return back()->with('error', 'Les départements Communication, Commercial et Pédagogique ne peuvent pas être supprimés.');
        }

        if ($departement->isInUse()) {
            return back()->with('error', 'Ce département contient encore des utilisateurs ou des données.');
        }

        $nom = $departement->nom;
        $departement->delete();

        app(ActivityLogger::class)->log(
            'user',
            $request->user()->name.' a supprimé le département « '.$nom.' »',
            $request->user(),
            'delete',
            'Départements',
            route('users.index')
        );

        return redirect()->route('users.index')->with('success', 'Département supprimé.');
    }

    public function switch(Request $request)
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $data = $request->validate([
            'departement_id' => ['required', 'exists:departements,id'],
        ]);

        session(['departement_actif_id' => (int) $data['departement_id']]);

        return back();
    }
}
