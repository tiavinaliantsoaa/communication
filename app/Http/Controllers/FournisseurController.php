<?php

namespace App\Http\Controllers;

use App\Models\Fournisseur;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class FournisseurController extends Controller
{
    public function index()
    {
        $fournisseurs = Fournisseur::orderBy('nom')->paginate(15);

        return view('fournisseurs.index', compact('fournisseurs'));
    }

    public function create()
    {
        return view('fournisseurs.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'service' => ['nullable', 'string', 'max:255'],
        ]);

        $fournisseur = Fournisseur::create($validated);

        app(ActivityLogger::class)->log(
            'fournisseur',
            auth()->user()->name.' a créé le fournisseur « '.$fournisseur->nom.' »',
            auth()->user(),
            'create',
            'Fournisseurs',
            route('fournisseurs.index'),
            $fournisseur
        );

        return redirect()->route('fournisseurs.index')->with('success', 'Fournisseur créé avec succès.');
    }

    public function edit(Fournisseur $fournisseur)
    {
        return view('fournisseurs.edit', compact('fournisseur'));
    }

    public function update(Request $request, Fournisseur $fournisseur)
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'service' => ['nullable', 'string', 'max:255'],
        ]);

        $fournisseur->update($validated);

        app(ActivityLogger::class)->log(
            'fournisseur',
            auth()->user()->name.' a modifié le fournisseur « '.$fournisseur->nom.' »',
            auth()->user(),
            'update',
            'Fournisseurs',
            route('fournisseurs.index'),
            $fournisseur
        );

        return redirect()->route('fournisseurs.index')->with('success', 'Fournisseur mis à jour avec succès.');
    }

    public function destroy(Fournisseur $fournisseur)
    {
        $nom = $fournisseur->nom;
        $fournisseur->delete();

        app(ActivityLogger::class)->log(
            'fournisseur',
            auth()->user()->name.' a supprimé le fournisseur « '.$nom.' »',
            auth()->user(),
            'delete',
            'Fournisseurs',
            route('fournisseurs.index')
        );

        return redirect()->route('fournisseurs.index')->with('success', 'Fournisseur supprimé avec succès.');
    }
}
