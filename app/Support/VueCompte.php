<?php

namespace App\Support;

use App\Models\EcoleEnseignant;
use App\Models\EcoleEtudiant;
use App\Models\User;
use Illuminate\Support\Collection;

class VueCompte
{
    public const SESSION = 'vue_compte';

    /**
     * @return array{type: string, id: int}|null
     */
    public static function current(): ?array
    {
        $user = auth()->user();
        if (! $user || ! $user->isSuperAdmin()) {
            return null;
        }

        $value = session(self::SESSION);
        if (! is_string($value) || ! str_contains($value, ':')) {
            return null;
        }

        [$type, $id] = explode(':', $value, 2);
        if (! in_array($type, ['enseignant', 'etudiant', 'parent'], true) || ! ctype_digit($id)) {
            return null;
        }

        return ['type' => $type, 'id' => (int) $id];
    }

    public static function cle(): ?string
    {
        $vue = self::current();

        return $vue ? $vue['type'].':'.$vue['id'] : null;
    }

    public static function oublier(): void
    {
        session()->forget(self::SESSION);
    }

    public static function definir(string $type, int $id): void
    {
        $departementId = match ($type) {
            'enseignant' => EcoleEnseignant::withoutGlobalScope('departement')->findOrFail($id)->departement_id,
            'etudiant' => EcoleEtudiant::withoutGlobalScopes(['departement', 'annee'])->findOrFail($id)->departement_id,
            'parent' => User::query()->where('role', 'parent')->findOrFail($id)->departement_id,
            default => abort(404),
        };

        session([
            self::SESSION => $type.':'.$id,
            'departement_actif_id' => (int) $departementId,
        ]);
    }

    public static function enseignant(): ?EcoleEnseignant
    {
        $vue = self::current();
        if (! $vue || $vue['type'] !== 'enseignant') {
            return null;
        }

        return EcoleEnseignant::query()->find($vue['id']);
    }

    /**
     * Students whose dashboard is being previewed: one student, or a parent's children.
     *
     * @return Collection<int, EcoleEtudiant>
     */
    public static function etudiants(): Collection
    {
        $vue = self::current();
        if (! $vue) {
            return collect();
        }

        if ($vue['type'] === 'etudiant') {
            $etudiant = EcoleEtudiant::query()->find($vue['id']);

            return $etudiant ? collect([$etudiant]) : collect();
        }

        if ($vue['type'] === 'parent') {
            return EcoleEtudiant::query()
                ->where('parent_user_id', $vue['id'])
                ->orderBy('nom')
                ->orderBy('prenom')
                ->get();
        }

        return collect();
    }

    public static function libelle(): ?string
    {
        $vue = self::current();
        if (! $vue) {
            return null;
        }

        if ($vue['type'] === 'enseignant') {
            return self::enseignant()?->nom_complet;
        }

        if ($vue['type'] === 'etudiant') {
            return self::etudiants()->first()?->nom_complet;
        }

        if ($vue['type'] === 'parent') {
            return User::query()->find($vue['id'])?->name;
        }

        return null;
    }
}
