<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Depense extends Model
{
    public const STATUTS = [
        'en_attente' => 'En attente',
        'valide' => 'Approuvé',
        'paye' => 'Payé',
    ];

    public const STATUT_APPROUVE = 'valide';

    public static function statutsForUser(?User $user = null): array
    {
        $statuts = self::STATUTS;

        if (! $user?->canApproveDepense()) {
            unset($statuts[self::STATUT_APPROUVE]);
        }

        return $statuts;
    }


    public const CATEGORIES = [
        'sponsoring_reseaux' => 'Boost Facebook',
        'production_contenu' => 'Production contenu',
        'impression' => 'Impression',
        'goodies_evenements' => 'Goodies / Événements',
    ];

    public const MODES_PAIEMENT = [
        'acompte' => 'Acompte',
        'totalite' => 'Totalité',
    ];

    protected $fillable = [
        'fournisseur',
        'objet',
        'campagne',
        'montant',
        'statut',
        'mode_paiement',
        'reste_a_payer',
        'categorie',
        'date_depense',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'reste_a_payer' => 'decimal:2',
        'date_depense' => 'date',
    ];

    public function getStatutLabelAttribute(): string
    {
        if ($this->isPayePartiellement()) {
            return 'Payé partiellement';
        }

        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    public function getStatutAfficheAttribute(): string
    {
        if ($this->isPayePartiellement()) {
            return 'paye_partiellement';
        }

        return $this->statut;
    }

    public function isPayePartiellement(): bool
    {
        return $this->statut === 'paye' && $this->mode_paiement === 'acompte';
    }

    public function getModePaiementLabelAttribute(): ?string
    {
        if (! $this->mode_paiement) {
            return null;
        }

        return self::MODES_PAIEMENT[$this->mode_paiement] ?? $this->mode_paiement;
    }
}
