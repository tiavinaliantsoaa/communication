<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class DepartementScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();
        if (! $user || ! $user->restrictsDepartementData()) {
            return;
        }

        $departementId = $user->currentDepartementId();
        if (! $departementId) {
            $builder->whereRaw('0 = 1');

            return;
        }

        if ($this->directionVoitLesCalendriers($departementId, $model)) {
            return;
        }

        $table = $model->getTable();
        if ($model instanceof ProjetCarte) {
            $builder->where(function ($query) use ($table, $departementId) {
                $query->where($table.'.departement_id', $departementId)
                    ->orWhereExists(function ($member) use ($table, $departementId) {
                        $this->memberOfDepartement($member, $table.'.id', $departementId);
                    });
            });

            return;
        }

        $carteIdColumn = match ($model::class) {
            ProjetChecklist::class, ProjetCommentaire::class, ProjetPieceJointe::class, ProjetActivite::class => $table.'.projet_carte_id',
            default => null,
        };
        if ($carteIdColumn) {
            $this->whereCarteVisible($builder, $table, $carteIdColumn, $departementId);

            return;
        }

        if ($model instanceof ProjetChecklistItem) {
            $this->whereOwnedOrLinked($builder, $table, function ($sub) use ($table, $departementId) {
                $sub->selectRaw('1')
                    ->from('projet_checklists')
                    ->join('projet_cartes', 'projet_cartes.id', '=', 'projet_checklists.projet_carte_id')
                    ->whereColumn('projet_checklists.id', $table.'.projet_checklist_id')
                    ->where(function ($query) use ($departementId) {
                        $query->where('projet_cartes.departement_id', $departementId)
                            ->orWhereExists(function ($member) use ($departementId) {
                                $this->memberOfDepartement($member, 'projet_cartes.id', $departementId);
                            });
                    });
            }, $departementId);

            return;
        }

        if ($model instanceof ProjetCommentaireImage || $model instanceof ProjetCommentaireReaction) {
            $this->whereOwnedOrLinked($builder, $table, function ($sub) use ($table, $departementId) {
                $sub->selectRaw('1')
                    ->from('projet_commentaires')
                    ->join('projet_cartes', 'projet_cartes.id', '=', 'projet_commentaires.projet_carte_id')
                    ->whereColumn('projet_commentaires.id', $table.'.projet_commentaire_id')
                    ->where(function ($query) use ($departementId) {
                        $query->where('projet_cartes.departement_id', $departementId)
                            ->orWhereExists(function ($member) use ($departementId) {
                                $this->memberOfDepartement($member, 'projet_cartes.id', $departementId);
                            });
                    });
            }, $departementId);

            return;
        }

        $builder->where($table.'.departement_id', $departementId);
    }

    /**
     * A row is visible when it belongs to the department, when its card does,
     * or when that card includes a member of the department.
     */
    private function whereCarteVisible(Builder $builder, string $table, string $carteIdColumn, int $departementId): void
    {
        $this->whereOwnedOrLinked($builder, $table, function ($sub) use ($carteIdColumn, $departementId) {
            $sub->selectRaw('1')
                ->from('projet_cartes')
                ->whereColumn('projet_cartes.id', $carteIdColumn)
                ->where(function ($query) use ($departementId) {
                    $query->where('projet_cartes.departement_id', $departementId)
                        ->orWhereExists(function ($member) use ($departementId) {
                            $this->memberOfDepartement($member, 'projet_cartes.id', $departementId);
                        });
                });
        }, $departementId);
    }

    private function whereOwnedOrLinked(Builder $builder, string $table, \Closure $linked, int $departementId): void
    {
        $builder->where(function ($query) use ($table, $linked, $departementId) {
            $query->where($table.'.departement_id', $departementId)
                ->orWhereExists($linked);
        });
    }

    /**
     * Direction oversees the editorial calendars of every department.
     * Other sections stay limited to the active department.
     */
    private function directionVoitLesCalendriers(int $departementId, Model $model): bool
    {
        if (! in_array($model::class, [EditorialEvent::class, EditorialEventVisuel::class], true)) {
            return false;
        }

        return Departement::query()->whereKey($departementId)->value('slug') === 'direction';
    }

    private function memberOfDepartement($sub, string $carteIdColumn, int $departementId): void
    {
        $sub->selectRaw('1')
            ->from('projet_carte_user')
            ->join('users', 'users.id', '=', 'projet_carte_user.user_id')
            ->whereColumn('projet_carte_user.projet_carte_id', $carteIdColumn)
            ->where('users.departement_id', $departementId);
    }

    /**
     * Tables whose rows belong to a single department.
     *
     * @return list<string>
     */
    public static function tables(): array
    {
        return [
            'budgets',
            'budget_annuels',
            'depenses',
            'depense_categories',
            'enseignants',
            'fournisseurs',
            'campagnes',
            'stocks',
            'stock_mouvements',
            'evenements',
            'editorial_events',
            'editorial_event_visuels',
            'tracked_links',
            'projet_tableaux',
            'projet_listes',
            'projet_etiquettes',
            'projet_cartes',
            'projet_checklists',
            'projet_checklist_items',
            'projet_commentaires',
            'projet_commentaire_images',
            'projet_commentaire_reactions',
            'projet_pieces_jointes',
            'projet_activites',
            'activity_logs',
            'online_tests',
            'ecole_groupes',
            'ecole_etudiants',
            'ecole_seances',
            'ecole_assiduites',
            'ecole_salles',
            'ecole_presences',
        ];
    }

    /**
     * @return list<class-string<Model>>
     */
    public static function models(): array
    {
        return [
            OnlineTest::class,
            Budget::class,
            BudgetAnnuel::class,
            Depense::class,
            DepenseCategorie::class,
            Enseignant::class,
            Fournisseur::class,
            Campagne::class,
            Stock::class,
            StockMouvement::class,
            Evenement::class,
            EditorialEvent::class,
            EditorialEventVisuel::class,
            TrackedLink::class,
            ProjetTableau::class,
            ProjetListe::class,
            ProjetEtiquette::class,
            ProjetCarte::class,
            ProjetChecklist::class,
            ProjetChecklistItem::class,
            ProjetCommentaire::class,
            ProjetCommentaireImage::class,
            ProjetCommentaireReaction::class,
            ProjetPieceJointe::class,
            ProjetActivite::class,
            ActivityLog::class,
            EcoleGroupe::class,
            EcoleEtudiant::class,
            EcoleMatiere::class,
            EcoleEnseignant::class,
            EcoleSeance::class,
            EcoleAssiduite::class,
            EcoleSalle::class,
            EcolePresence::class,
        ];
    }
}
