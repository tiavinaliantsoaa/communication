<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    public const MODULES = [
        'projet' => 'Gestion de projet',
        'editorial' => 'Calendrier éditorial',
        'evenement' => 'Événements',
        'depense' => 'Dépenses',
        'campagne' => 'Campagnes',
        'budget' => 'Budget mensuel',
        'budget_annuel' => 'Budget annuel',
        'stock' => 'Stocks',
        'mouvement' => 'Entrées / Sorties',
        'fournisseur' => 'Fournisseurs',
        'user' => 'Utilisateurs',
        'autre' => 'Autre',
    ];

    protected $fillable = [
        'user_id',
        'module',
        'action',
        'titre',
        'description',
        'url',
        'subject_type',
        'subject_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function getModuleLabelAttribute(): string
    {
        return self::MODULES[$this->module] ?? ucfirst($this->module);
    }

    public function getModuleColorAttribute(): string
    {
        return match ($this->module) {
            'projet' => 'bg-sky-100 text-sky-800',
            'editorial' => 'bg-violet-100 text-violet-800',
            'evenement' => 'bg-amber-100 text-amber-800',
            'depense' => 'bg-emerald-100 text-emerald-800',
            'campagne' => 'bg-blue-100 text-blue-800',
            'budget', 'budget_annuel' => 'bg-indigo-100 text-indigo-800',
            'stock', 'mouvement' => 'bg-orange-100 text-orange-800',
            'fournisseur' => 'bg-teal-100 text-teal-800',
            'user' => 'bg-rose-100 text-rose-800',
            default => 'bg-slate-100 text-slate-700',
        };
    }
}
