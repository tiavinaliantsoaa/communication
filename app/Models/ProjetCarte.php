<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjetCarte extends Model
{
    protected $table = 'projet_cartes';

    /** @deprecated Kept for migration defaults / seeder labels */
    public const STATUTS = [
        'a_faire' => 'À faire',
        'en_attente' => 'En attente',
        'en_cours' => 'En cours',
        'en_attente_validation' => 'En attente de validation',
        'bloque' => 'Bloqué',
        'termine' => 'Terminé',
    ];

    protected $fillable = [
        'titre',
        'description',
        'projet_liste_id',
        'position',
        'date_debut',
        'date_fin',
        'created_by',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'datetime',
        'position' => 'integer',
    ];

    public function liste(): BelongsTo
    {
        return $this->belongsTo(ProjetListe::class, 'projet_liste_id');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function membres(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'projet_carte_user');
    }

    public function etiquettes(): BelongsToMany
    {
        return $this->belongsToMany(ProjetEtiquette::class, 'projet_carte_etiquette');
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(ProjetChecklist::class)->orderBy('position');
    }

    public function commentaires(): HasMany
    {
        return $this->hasMany(ProjetCommentaire::class)->latest();
    }

    public function piecesJointes(): HasMany
    {
        return $this->hasMany(ProjetPieceJointe::class)->latest();
    }

    public function activites(): HasMany
    {
        return $this->hasMany(ProjetActivite::class)->latest();
    }

    public function getStatutLabelAttribute(): string
    {
        return $this->liste?->nom ?? '—';
    }

    public function isDone(): bool
    {
        return ($this->liste?->slug ?? '') === 'termine'
            || strcasecmp((string) $this->liste?->nom, 'Terminé') === 0;
    }

    public function isOverdue(): bool
    {
        if (! $this->date_fin || $this->isDone()) {
            return false;
        }

        return $this->date_fin->isPast();
    }

    public function dateBadgeLabel(): ?string
    {
        if (! $this->date_debut && ! $this->date_fin) {
            return null;
        }

        $format = function (?Carbon $d) {
            if (! $d) {
                return null;
            }

            return $d->locale('fr')->isoFormat('D MMM');
        };

        if ($this->date_debut && $this->date_fin) {
            return $format($this->date_debut).' - '.$format($this->date_fin);
        }

        return $format($this->date_fin ?? $this->date_debut);
    }

    public function checklistProgress(): array
    {
        $items = $this->checklists->flatMap->items;
        $total = $items->count();
        $done = $items->where('fait', true)->count();
        $percent = $total > 0 ? (int) round(($done / $total) * 100) : 0;

        return [
            'done' => $done,
            'total' => $total,
            'percent' => $percent,
        ];
    }

    public function findTermineListe(): ?ProjetListe
    {
        if (! $this->relationLoaded('liste')) {
            $this->load('liste');
        }

        $tableauId = $this->liste?->projet_tableau_id;
        if (! $tableauId) {
            return null;
        }

        return ProjetListe::query()
            ->where('projet_tableau_id', $tableauId)
            ->where(function ($q) {
                $q->where('slug', 'termine')
                    ->orWhereRaw('LOWER(nom) = ?', ['terminé']);
            })
            ->first();
    }

    /**
     * Move the card to the "Terminé" list when all checklist items are done.
     */
    public function moveToTermineIfComplete(): bool
    {
        if ($this->isDone()) {
            return false;
        }

        $this->load(['checklists.items', 'liste']);
        $progress = $this->checklistProgress();

        if ($progress['total'] === 0 || $progress['percent'] < 100) {
            return false;
        }

        $termine = $this->findTermineListe();
        if (! $termine) {
            return false;
        }

        $maxPos = (int) static::where('projet_liste_id', $termine->id)->max('position');

        $this->update([
            'projet_liste_id' => $termine->id,
            'position' => $maxPos + 1,
        ]);

        $this->setRelation('liste', $termine);

        return true;
    }
}
