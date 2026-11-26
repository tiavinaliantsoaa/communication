<?php

namespace App\Models;

use App\Support\AnneeUniversitaire;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\DB;

class ParentScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();
        if (! $user || $user->role !== 'parent') {
            return;
        }

        $table = $model->getTable();
        $groupeIds = $this->groupeIds($user);
        $eleveIds = $this->eleveIds($user);

        if ($model instanceof EcoleEtudiant) {
            $builder->whereIn($table.'.id', $eleveIds);

            return;
        }

        if ($model instanceof EcoleAssiduite || $model instanceof EcolePresence) {
            $builder->whereIn($table.'.ecole_etudiant_id', $eleveIds);

            return;
        }

        if ($model instanceof EcoleGroupe) {
            $builder->whereIn($table.'.id', $groupeIds);

            return;
        }

        if ($model instanceof EcoleSeance) {
            $builder->whereIn($table.'.ecole_groupe_id', $groupeIds);
        }
    }

    /**
     * @return list<int>
     */
    private function eleveIds(User $user): array
    {
        return DB::table('ecole_etudiants')
            ->join('ecole_groupes', 'ecole_groupes.id', '=', 'ecole_etudiants.ecole_groupe_id')
            ->where('ecole_etudiants.parent_user_id', $user->id)
            ->where('ecole_etudiants.departement_id', $user->currentDepartementId())
            ->where('ecole_groupes.annee_universitaire', AnneeUniversitaire::selected())
            ->pluck('ecole_etudiants.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return list<int>
     */
    private function groupeIds(User $user): array
    {
        return DB::table('ecole_etudiants')
            ->join('ecole_groupes', 'ecole_groupes.id', '=', 'ecole_etudiants.ecole_groupe_id')
            ->where('ecole_etudiants.parent_user_id', $user->id)
            ->where('ecole_etudiants.departement_id', $user->currentDepartementId())
            ->where('ecole_groupes.annee_universitaire', AnneeUniversitaire::selected())
            ->pluck('ecole_etudiants.ecole_groupe_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
