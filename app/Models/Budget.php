<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Budget extends Model
{
    protected $fillable = ['montant', 'annee', 'mois'];

    protected $casts = [
        'montant' => 'decimal:2',
    ];

    public function lignes(): HasMany
    {
        return $this->hasMany(BudgetLigne::class)->orderBy('id');
    }

    public function montantReparti(): float
    {
        return (float) $this->lignes()->sum('montant');
    }

    public function montantNonReparti(): float
    {
        return round((float) $this->montant - $this->montantReparti(), 2);
    }

    /**
     * @param  list<array{depense_categorie_id?: mixed, montant?: mixed}>  $lignes
     */
    public function syncLignes(array $lignes): void
    {
        $keepIds = [];

        foreach ($lignes as $row) {
            $categorieId = (int) ($row['depense_categorie_id'] ?? 0);
            $montant = round((float) ($row['montant'] ?? 0), 2);

            if ($categorieId <= 0 || $montant < 0) {
                continue;
            }

            $ligne = $this->lignes()->updateOrCreate(
                ['depense_categorie_id' => $categorieId],
                ['montant' => $montant]
            );
            $keepIds[] = $ligne->id;
        }

        if ($keepIds === []) {
            $this->lignes()->delete();
        } else {
            $this->lignes()->whereNotIn('id', $keepIds)->delete();
        }
    }
}
