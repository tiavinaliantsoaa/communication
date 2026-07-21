<?php

namespace Database\Seeders;

use App\Models\ProjetActivite;
use App\Models\ProjetCarte;
use App\Models\ProjetChecklist;
use App\Models\ProjetEtiquette;
use App\Models\ProjetListe;
use App\Models\ProjetTableau;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjetSeeder extends Seeder
{
    public function run(): void
    {
        if (! ProjetEtiquette::exists()) {
            ProjetEtiquette::ensureDefaults();
        }

        if (ProjetCarte::exists()) {
            return;
        }

        $tableau = ProjetTableau::current();

        if ($tableau->listes()->count() === 0) {
            $position = 0;
            foreach (ProjetCarte::STATUTS as $slug => $nom) {
                $tableau->listes()->create([
                    'nom' => $nom,
                    'slug' => $slug,
                    'position' => $position++,
                ]);
            }
        }

        $listes = ProjetListe::where('projet_tableau_id', $tableau->id)->get()->keyBy('slug');

        $etiquettes = ProjetEtiquette::all();
        if ($etiquettes->isEmpty()) {
            ProjetEtiquette::ensureDefaults();
            $etiquettes = ProjetEtiquette::all();
        }

        $users = User::all();
        $byInitial = [
            'MA' => $users->first(fn ($u) => str_contains(strtolower($u->name), 'matthieu') || str_contains(strtolower($u->email), 'matthieu')),
            'AD' => $users->first(fn ($u) => str_contains(strtolower($u->email), 'admin@')),
            'SA' => $users->first(fn ($u) => $u->isSuperAdmin()),
        ];

        $memberIds = fn (...$keys) => collect($keys)
            ->map(fn ($k) => $byInitial[$k]?->id)
            ->filter()
            ->values()
            ->all();

        $cards = [
            [
                'titre' => 'Simulation signalétique',
                'liste' => 'a_faire',
                'etiquettes' => ['À traiter'],
                'date_fin' => '2026-06-16',
            ],
            [
                'titre' => 'BDE ESCM',
                'liste' => 'a_faire',
                'etiquettes' => ['Communication'],
                'date_fin' => '2026-06-28',
                'membres' => ['MA', 'AD', 'SA'],
                'commentaires' => 1,
            ],
            [
                'titre' => 'Agent IA',
                'liste' => 'a_faire',
                'etiquettes' => ['À traiter'],
                'date_debut' => '2026-06-15',
                'date_fin' => '2026-09-30',
                'description' => "Exploration d'un agent IA pour la communication ESCM.",
                'membres' => ['SA'],
                'checklist' => ['Brief', 'Prototype', 'Tests', 'Mise en prod'],
                'checklist_done' => 1,
            ],
            [
                'titre' => 'Créer une liste de diffusion ESCM / Copil',
                'liste' => 'en_attente',
            ],
            [
                'titre' => 'Tour Eiffel ESCM',
                'liste' => 'en_attente',
                'etiquettes' => ['À traiter'],
                'membres' => ['MA', 'AD'],
                'checklist' => ['Validation concept'],
            ],
            [
                'titre' => 'Sortie de la 7ème promotion',
                'liste' => 'en_cours',
                'etiquettes' => ['Urgent & Important'],
                'date_debut' => '2026-06-01',
                'date_fin' => '2026-09-30',
                'description' => 'Organisation de la sortie de promotion — supports, partenaires, logistique.',
                'membres' => ['SA', 'AD'],
                'checklist' => ['Lieu', 'Invitations', 'Visuels', 'Sponsors', 'Programme', 'Goodies', 'Budget'],
                'checklist_done' => 1,
            ],
            [
                'titre' => 'Supports de communication',
                'liste' => 'en_cours',
                'etiquettes' => ['À traiter'],
                'description' => 'Flyers, kakémonos, posts réseaux, templates Canva.',
                'membres' => ['MA', 'AD', 'SA'],
                'checklist' => array_map(fn ($i) => "Élément $i", range(1, 19)),
                'checklist_done' => 12,
            ],
            [
                'titre' => 'Projet Legacy',
                'liste' => 'en_cours',
                'etiquettes' => ['Urgent & Important'],
                'date_debut' => '2026-05-07',
                'date_fin' => '2026-05-22 09:49:00',
                'description' => "Demande de sponsoring\n\nProgramme professionnel pour cadres dirigeants & CEO\nESCM Formation Professionnelle\n\nObjet : Demande de sponsoring pour le programme Agritud\nCible sponsoring : Entreprises familiales\nPublic du programme : Cadres dirigeants, CEO et décideurs\nTarif de référence : 400 € / jour / personne\nMontant du sponsoring : À définir selon le niveau d'engagement souhaité\n\nhttps://canva.link/vuqs63xwxlb7jao",
                'membres' => ['MA', 'SA', 'AD'],
                'comment_texts' => [
                    "Modifications :\n• Titre: Legacy\n• Slogan: Transformer la réussite en héritage",
                    'Fichier excel JR et Noroanja',
                ],
            ],
            [
                'titre' => 'STTE',
                'liste' => 'en_attente_validation',
                'etiquettes' => ['À traiter', 'Communication', 'Partenariat'],
                'membres' => ['AD'],
            ],
            [
                'titre' => 'Carte étudiante électronique',
                'liste' => 'bloque',
                'etiquettes' => ['Terminé'],
                'membres' => ['MA'],
                'checklist' => ['Validation technique'],
            ],
            [
                'titre' => 'Porte diplôme',
                'liste' => 'bloque',
                'etiquettes' => ['À traiter'],
                'date_debut' => '2025-10-01',
                'date_fin' => '2026-06-30',
                'description' => 'Conception et production des portes-diplômes.',
                'membres' => ['AD'],
            ],
            [
                'titre' => 'Plaques de Salle',
                'liste' => 'termine',
                'etiquettes' => ['Terminé'],
                'description' => 'Signalétique des salles ESCM livrée.',
                'membres' => ['AD', 'MA'],
                'checklist' => ['Maquette', 'Validation', 'Impression', 'Pose', 'Photos'],
                'checklist_done' => 5,
            ],
            [
                'titre' => 'Budget Communication',
                'liste' => 'termine',
                'etiquettes' => ['À traiter'],
                'date_debut' => '2025-06-01',
                'date_fin' => '2025-06-16',
                'description' => 'Budget annuel communication validé.',
                'membres' => ['MA', 'SA', 'AD'],
                'checklist' => array_map(fn ($i) => "Ligne $i", range(1, 9)),
                'checklist_done' => 9,
            ],
        ];

        foreach ($cards as $index => $data) {
            $liste = $listes[$data['liste']] ?? $listes->first();
            if (! $liste) {
                continue;
            }

            $carte = ProjetCarte::create([
                'titre' => $data['titre'],
                'description' => $data['description'] ?? null,
                'projet_liste_id' => $liste->id,
                'position' => $index,
                'date_debut' => $data['date_debut'] ?? null,
                'date_fin' => $data['date_fin'] ?? null,
                'created_by' => $byInitial['MA']?->id ?? $users->first()?->id,
            ]);

            if (! empty($data['etiquettes'])) {
                $ids = $etiquettes->whereIn('nom', $data['etiquettes'])->pluck('id');
                $carte->etiquettes()->sync($ids);
            }

            if (! empty($data['membres'])) {
                $carte->membres()->sync($memberIds(...$data['membres']));
            }

            if (! empty($data['checklist'])) {
                $checklist = ProjetChecklist::create([
                    'projet_carte_id' => $carte->id,
                    'titre' => 'Checklist',
                    'position' => 0,
                ]);
                $done = (int) ($data['checklist_done'] ?? 0);
                foreach ($data['checklist'] as $i => $titre) {
                    $checklist->items()->create([
                        'titre' => $titre,
                        'fait' => $i < $done,
                        'position' => $i,
                    ]);
                }
            }

            if (! empty($data['comment_texts'])) {
                foreach ($data['comment_texts'] as $contenu) {
                    $carte->commentaires()->create([
                        'user_id' => $byInitial['MA']?->id ?? $users->first()->id,
                        'contenu' => $contenu,
                    ]);
                }
            } elseif (! empty($data['commentaires'])) {
                $carte->commentaires()->create([
                    'user_id' => $byInitial['MA']?->id ?? $users->first()->id,
                    'contenu' => 'Note initiale sur cette carte.',
                ]);
            }

            ProjetActivite::create([
                'projet_carte_id' => $carte->id,
                'user_id' => $byInitial['MA']?->id,
                'message' => ($byInitial['MA']?->name ?? 'Système').' a ajouté cette carte à '.$liste->nom,
            ]);
        }
    }
}
