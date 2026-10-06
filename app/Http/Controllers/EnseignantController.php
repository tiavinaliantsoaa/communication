<?php

namespace App\Http\Controllers;

use App\Models\Enseignant;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EnseignantController extends Controller
{
    public function index()
    {
        $enseignants = Enseignant::query()
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        return view('enseignants.index', compact('enseignants'));
    }

    public function create()
    {
        return view('enseignants.create');
    }

    public function store(Request $request)
    {
        $enseignant = Enseignant::create($this->validated($request));
        $this->storePhoto($request, $enseignant);

        app(ActivityLogger::class)->log(
            'enseignant',
            $request->user()->name.' a ajouté l’enseignant « '.$enseignant->nom_complet.' »',
            $request->user(),
            'create',
            'CV enseignant',
            route('enseignants.show', $enseignant),
            $enseignant
        );

        return redirect()->route('enseignants.show', $enseignant)->with('success', 'Enseignant ajouté.');
    }

    public function show(Enseignant $enseignant)
    {
        return view('enseignants.show', compact('enseignant'));
    }

    public function edit(Enseignant $enseignant)
    {
        return view('enseignants.edit', compact('enseignant'));
    }

    public function update(Request $request, Enseignant $enseignant)
    {
        $enseignant->update($this->validated($request));
        $this->storePhoto($request, $enseignant);

        app(ActivityLogger::class)->log(
            'enseignant',
            $request->user()->name.' a modifié le CV de « '.$enseignant->nom_complet.' »',
            $request->user(),
            'update',
            'CV enseignant',
            route('enseignants.show', $enseignant),
            $enseignant
        );

        return redirect()->route('enseignants.show', $enseignant)->with('success', 'CV mis à jour.');
    }

    public function destroy(Request $request, Enseignant $enseignant)
    {
        $nom = $enseignant->nom_complet;
        $enseignant->delete();

        app(ActivityLogger::class)->log(
            'enseignant',
            $request->user()->name.' a supprimé l’enseignant « '.$nom.' »',
            $request->user(),
            'delete',
            'CV enseignant',
            route('enseignants.index')
        );

        return redirect()->route('enseignants.index')->with('success', 'Enseignant supprimé.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'prenom' => ['required', 'string', 'max:100'],
            'nom' => ['required', 'string', 'max:100'],
            'fonction' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:50'],
            'matieres' => ['nullable', 'string', 'max:255'],
            'biographie' => ['nullable', 'string'],
            'formation' => ['nullable', 'string'],
            'experience' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
    }

    private function storePhoto(Request $request, Enseignant $enseignant): void
    {
        if (! $request->hasFile('photo')) {
            return;
        }

        if ($enseignant->photo_path) {
            Storage::disk('public')->delete($enseignant->photo_path);
        }

        $enseignant->update([
            'photo_path' => $request->file('photo')->store('enseignants', 'public'),
        ]);
    }
}
