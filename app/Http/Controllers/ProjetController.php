<?php

namespace App\Http\Controllers;

use App\Models\ProjetActivite;
use App\Models\ProjetCarte;
use App\Models\ProjetChecklist;
use App\Models\ProjetChecklistItem;
use App\Models\ProjetListe;
use App\Models\ProjetEtiquette;
use App\Models\ProjetPieceJointe;
use App\Models\ProjetTableau;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProjetController extends Controller
{
    public function index()
    {
        ProjetEtiquette::ensureDefaults();

        $tableau = ProjetTableau::current();
        $tableau->load(['listes.cartes' => function ($q) {
            $q->with([
                'etiquettes',
                'membres',
                'checklists.items',
                'commentaires',
                'piecesJointes',
            ])->orderBy('position');
        }]);

        $listes = $tableau->listes;
        $etiquettes = ProjetEtiquette::orderBy('nom')->get();
        $users = User::orderBy('name')->get(['id', 'name', 'email', 'avatar_path']);

        return view('projets.index', [
            'title' => 'Gestion de projet',
            'subtitle' => 'Tableau Kanban — '.$tableau->nom,
            'tableau' => $tableau,
            'listes' => $listes,
            'etiquettes' => $etiquettes,
            'users' => $users,
        ]);
    }

    public function storeListe(Request $request)
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
        ]);

        $tableau = ProjetTableau::current();
        $liste = $tableau->listes()->create([
            'nom' => $data['nom'],
            'slug' => ProjetListe::makeSlug($data['nom']),
            'position' => (int) $tableau->listes()->max('position') + 1,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'id' => $liste->id, 'nom' => $liste->nom]);
        }

        return back()->with('success', 'Liste ajoutée.');
    }

    public function updateListe(Request $request, ProjetListe $liste)
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
        ]);

        $liste->update(['nom' => $data['nom']]);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Liste renommée.');
    }

    public function destroyListe(Request $request, ProjetListe $liste)
    {
        if ($liste->cartes()->exists()) {
            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => 'Déplacez ou supprimez les cartes avant de supprimer la liste.'], 422);
            }

            return back()->with('error', 'Déplacez ou supprimez les cartes avant de supprimer la liste.');
        }

        $liste->delete();

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Liste supprimée.');
    }

    public function updateBackground(Request $request)
    {
        $data = $request->validate([
            'background' => ['nullable', 'image', 'max:5120'],
            'remove' => ['nullable', 'boolean'],
        ]);

        $tableau = ProjetTableau::current();

        if ($request->boolean('remove')) {
            if ($tableau->background_path) {
                Storage::disk('public')->delete($tableau->background_path);
            }
            $tableau->update(['background_path' => null]);

            return back()->with('success', 'Fond retiré.');
        }

        if (! $request->hasFile('background')) {
            return back()->with('error', 'Choisissez une image.');
        }

        if ($tableau->background_path) {
            Storage::disk('public')->delete($tableau->background_path);
        }

        $path = $request->file('background')->store('projets/backgrounds', 'public');
        $tableau->update(['background_path' => $path]);

        return back()->with('success', 'Fond d’écran mis à jour.');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'titre' => ['required', 'string', 'max:255'],
            'projet_liste_id' => ['required', 'exists:projet_listes,id'],
        ]);

        $liste = ProjetListe::findOrFail($data['projet_liste_id']);
        $position = (int) ProjetCarte::where('projet_liste_id', $liste->id)->max('position') + 1;

        $carte = ProjetCarte::create([
            'titre' => $data['titre'],
            'projet_liste_id' => $liste->id,
            'position' => $position,
            'created_by' => $request->user()->id,
        ]);

        $this->log($carte, $request->user()->id, $request->user()->name.' a ajouté cette carte à '.$liste->nom);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'id' => $carte->id]);
        }

        return back()->with('success', 'Carte créée.');
    }

    public function show(ProjetCarte $projet)
    {
        $projet->load([
            'liste',
            'etiquettes',
            'membres',
            'checklists.items',
            'commentaires.user',
            'piecesJointes',
            'activites.user',
            'createur',
        ]);

        $listes = ProjetListe::orderBy('position')->get(['id', 'nom']);

        return response()->json([
            'id' => $projet->id,
            'titre' => $projet->titre,
            'description' => $projet->description,
            'projet_liste_id' => $projet->projet_liste_id,
            'statut_label' => $projet->statut_label,
            'date_debut' => optional($projet->date_debut)->format('Y-m-d'),
            'date_fin' => optional($projet->date_fin)->format('Y-m-d\TH:i'),
            'date_badge' => $projet->dateBadgeLabel(),
            'is_overdue' => $projet->isOverdue(),
            'is_done' => $projet->isDone(),
            'listes' => $listes,
            'etiquettes' => $projet->etiquettes->map(fn ($e) => [
                'id' => $e->id,
                'nom' => $e->nom,
                'couleur' => $e->couleur,
                'classes' => $e->classes,
            ]),
            'membres' => $projet->membres->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'initials' => $u->initials(),
                'avatar_url' => $u->avatar_url,
            ]),
            'checklists' => $projet->checklists->map(fn ($c) => [
                'id' => $c->id,
                'titre' => $c->titre,
                'items' => $c->items->map(fn ($i) => [
                    'id' => $i->id,
                    'titre' => $i->titre,
                    'fait' => $i->fait,
                ]),
            ]),
            'commentaires' => $projet->commentaires->map(fn ($c) => [
                'id' => $c->id,
                'contenu' => $c->contenu,
                'user' => $c->user?->name,
                'initials' => $c->user?->initials(),
                'avatar_url' => $c->user?->avatar_url,
                'date' => $c->created_at->locale('fr')->isoFormat('D MMM YYYY, HH:mm'),
            ]),
            'pieces_jointes' => $projet->piecesJointes->map(fn ($p) => [
                'id' => $p->id,
                'nom' => $p->nom,
                'url' => $p->public_url,
            ]),
            'activites' => $projet->activites->map(fn ($a) => [
                'id' => $a->id,
                'message' => $a->message,
                'date' => $a->created_at->locale('fr')->isoFormat('D MMM YYYY'),
            ]),
            'checklist_progress' => $projet->checklistProgress(),
        ]);
    }

    public function update(Request $request, ProjetCarte $projet)
    {
        $data = $request->validate([
            'titre' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'projet_liste_id' => ['sometimes', 'exists:projet_listes,id'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date'],
        ]);

        $oldListeId = $projet->projet_liste_id;
        $projet->update($data);

        if (isset($data['projet_liste_id']) && (int) $data['projet_liste_id'] !== (int) $oldListeId) {
            $projet->load('liste');
            $this->log($projet, $request->user()->id, $request->user()->name.' a déplacé cette carte vers '.$projet->liste->nom);
        }

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Carte mise à jour.');
    }

    public function destroy(Request $request, ProjetCarte $projet)
    {
        $projet->delete();

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Carte supprimée.');
    }

    public function move(Request $request)
    {
        $data = $request->validate([
            'carte_id' => ['required', 'exists:projet_cartes,id'],
            'projet_liste_id' => ['required', 'exists:projet_listes,id'],
            'ordered_ids' => ['required', 'array'],
            'ordered_ids.*' => ['integer', 'exists:projet_cartes,id'],
        ]);

        $carte = ProjetCarte::findOrFail($data['carte_id']);
        $oldListeId = $carte->projet_liste_id;
        $liste = ProjetListe::findOrFail($data['projet_liste_id']);

        foreach ($data['ordered_ids'] as $index => $id) {
            ProjetCarte::where('id', $id)->update([
                'projet_liste_id' => $liste->id,
                'position' => $index,
            ]);
        }

        if ((int) $oldListeId !== (int) $liste->id) {
            $this->log($carte, $request->user()->id, $request->user()->name.' a déplacé cette carte vers '.$liste->nom);
        }

        return response()->json(['ok' => true]);
    }

    public function syncMembres(Request $request, ProjetCarte $projet)
    {
        $data = $request->validate([
            'user_ids' => ['array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $projet->membres()->sync($data['user_ids'] ?? []);

        return response()->json(['ok' => true]);
    }

    public function syncEtiquettes(Request $request, ProjetCarte $projet)
    {
        $data = $request->validate([
            'etiquette_ids' => ['array'],
            'etiquette_ids.*' => ['integer', 'exists:projet_etiquettes,id'],
        ]);

        $projet->etiquettes()->sync($data['etiquette_ids'] ?? []);

        return response()->json(['ok' => true]);
    }

    public function storeEtiquette(Request $request)
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:100'],
            'couleur' => ['required', 'string', Rule::in(array_keys(ProjetEtiquette::COULEURS))],
        ]);

        $etiquette = ProjetEtiquette::create($data);

        return response()->json([
            'ok' => true,
            'etiquette' => $etiquette->toBoardArray(),
        ], 201);
    }

    public function storeChecklist(Request $request, ProjetCarte $projet)
    {
        $data = $request->validate([
            'titre' => ['nullable', 'string', 'max:255'],
        ]);

        $checklist = $projet->checklists()->create([
            'titre' => $data['titre'] ?? 'Checklist',
            'position' => (int) $projet->checklists()->max('position') + 1,
        ]);

        return response()->json(['ok' => true, 'id' => $checklist->id, 'titre' => $checklist->titre]);
    }

    public function storeChecklistItem(Request $request, ProjetChecklist $checklist)
    {
        $data = $request->validate([
            'titre' => ['required', 'string', 'max:255'],
        ]);

        $item = $checklist->items()->create([
            'titre' => $data['titre'],
            'position' => (int) $checklist->items()->max('position') + 1,
        ]);

        return response()->json(['ok' => true, 'id' => $item->id]);
    }

    public function toggleChecklistItem(Request $request, ProjetChecklistItem $item)
    {
        $item->update(['fait' => ! $item->fait]);

        return response()->json(['ok' => true, 'fait' => $item->fait]);
    }

    public function destroyChecklistItem(ProjetChecklistItem $item)
    {
        $item->delete();

        return response()->json(['ok' => true]);
    }

    public function storeCommentaire(Request $request, ProjetCarte $projet)
    {
        $data = $request->validate([
            'contenu' => ['required', 'string'],
        ]);

        $commentaire = $projet->commentaires()->create([
            'user_id' => $request->user()->id,
            'contenu' => $data['contenu'],
        ]);

        return response()->json([
            'ok' => true,
            'commentaire' => [
                'id' => $commentaire->id,
                'contenu' => $commentaire->contenu,
                'user' => $request->user()->name,
                'initials' => $request->user()->initials(),
                'avatar_url' => $request->user()->avatar_url,
                'date' => $commentaire->created_at->locale('fr')->isoFormat('D MMM YYYY, HH:mm'),
            ],
        ]);
    }

    public function storePieceJointe(Request $request, ProjetCarte $projet)
    {
        $data = $request->validate([
            'fichier' => ['nullable', 'file', 'max:10240'],
            'url' => ['nullable', 'url', 'max:500'],
            'nom' => ['nullable', 'string', 'max:255'],
        ]);

        if (! $request->hasFile('fichier') && empty($data['url'])) {
            return response()->json(['ok' => false, 'message' => 'Fichier ou URL requis.'], 422);
        }

        $path = null;
        $nom = $data['nom'] ?? null;

        if ($request->hasFile('fichier')) {
            $file = $request->file('fichier');
            $path = $file->store('projets/'.$projet->id, 'public');
            $nom = $nom ?: $file->getClientOriginalName();
        }

        if (! $nom && ! empty($data['url'])) {
            $nom = parse_url($data['url'], PHP_URL_HOST) ?: 'Lien';
        }

        $piece = $projet->piecesJointes()->create([
            'nom' => $nom,
            'path' => $path,
            'url' => $data['url'] ?? null,
            'uploaded_by' => $request->user()->id,
        ]);

        $this->log($projet, $request->user()->id, $request->user()->name.' a ajouté une pièce jointe');

        return response()->json([
            'ok' => true,
            'piece' => [
                'id' => $piece->id,
                'nom' => $piece->nom,
                'url' => $piece->public_url,
            ],
        ]);
    }

    public function destroyPieceJointe(ProjetPieceJointe $piece)
    {
        if ($piece->path) {
            Storage::disk('public')->delete($piece->path);
        }
        $piece->delete();

        return response()->json(['ok' => true]);
    }

    protected function log(ProjetCarte $carte, ?int $userId, string $message): void
    {
        ProjetActivite::create([
            'projet_carte_id' => $carte->id,
            'user_id' => $userId,
            'message' => $message,
        ]);
    }
}
