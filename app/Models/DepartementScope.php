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

        $builder->where($model->getTable().'.departement_id', $departementId);
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
        ];
    }

    /**
     * @return list<class-string<Model>>
     */
    public static function models(): array
    {
        return [
            Budget::class,
            BudgetAnnuel::class,
            Depense::class,
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
        ];
    }
}
