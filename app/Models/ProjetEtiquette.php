<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjetEtiquette extends Model
{
    protected $table = 'projet_etiquettes';

    public const COULEURS = [
        'yellow' => ['bg' => 'bg-yellow-400', 'text' => 'text-yellow-900', 'badge' => 'bg-yellow-100 text-yellow-800'],
        'blue' => ['bg' => 'bg-blue-500', 'text' => 'text-white', 'badge' => 'bg-blue-100 text-blue-800'],
        'red' => ['bg' => 'bg-red-500', 'text' => 'text-white', 'badge' => 'bg-red-100 text-red-800'],
        'green' => ['bg' => 'bg-emerald-500', 'text' => 'text-white', 'badge' => 'bg-emerald-100 text-emerald-800'],
        'cyan' => ['bg' => 'bg-cyan-400', 'text' => 'text-cyan-900', 'badge' => 'bg-cyan-100 text-cyan-800'],
        'purple' => ['bg' => 'bg-purple-500', 'text' => 'text-white', 'badge' => 'bg-purple-100 text-purple-800'],
    ];

    protected $fillable = ['nom', 'couleur'];

    public function cartes(): BelongsToMany
    {
        return $this->belongsToMany(ProjetCarte::class, 'projet_carte_etiquette');
    }

    public function getClassesAttribute(): array
    {
        return self::COULEURS[$this->couleur] ?? self::COULEURS['yellow'];
    }

    /**
     * Create default labels if the table is empty.
     */
    public static function ensureDefaults(): void
    {
        if (static::exists()) {
            return;
        }

        foreach ([
            ['nom' => 'Urgent & Important', 'couleur' => 'red'],
            ['nom' => 'Urgent mais pas important', 'couleur' => 'yellow'],
            ['nom' => 'Important', 'couleur' => 'blue'],
            ['nom' => 'Pas urgent', 'couleur' => 'cyan'],
            ['nom' => 'Ni important ni urgent', 'couleur' => 'purple'],
            ['nom' => 'Communication', 'couleur' => 'green'],
            ['nom' => 'À traiter', 'couleur' => 'yellow'],
            ['nom' => 'Partenariat', 'couleur' => 'cyan'],
            ['nom' => 'Événement', 'couleur' => 'purple'],
            ['nom' => 'Terminé', 'couleur' => 'green'],
        ] as $etiquette) {
            static::create($etiquette);
        }
    }

    public function toBoardArray(): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'couleur' => $this->couleur,
            'classes' => $this->classes,
        ];
    }
}
