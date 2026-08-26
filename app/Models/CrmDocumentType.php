<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmDocumentType extends Model
{
    protected $fillable = [
        'label',
        'is_required',
        'actif',
        'position',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'actif' => 'boolean',
        'position' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('actif', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position')->orderBy('label');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CrmCandidateDocument::class);
    }
}
