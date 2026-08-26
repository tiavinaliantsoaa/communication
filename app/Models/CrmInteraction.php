<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmInteraction extends Model
{
    public const TYPES = [
        'reseaux_sociaux' => 'Réseaux sociaux',
        'appel' => 'Appel',
        'sms' => 'SMS',
        'whatsapp' => 'WhatsApp',
        'mail' => 'Mail',
    ];

    protected $fillable = [
        'crm_candidate_id',
        'user_id',
        'type',
        'commentaire',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(CrmCandidate::class, 'crm_candidate_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getTypeColorAttribute(): string
    {
        return match ($this->type) {
            'reseaux_sociaux' => 'bg-violet-50 text-violet-700',
            'appel' => 'bg-sky-50 text-sky-700',
            'sms' => 'bg-amber-50 text-amber-700',
            'whatsapp' => 'bg-emerald-50 text-emerald-700',
            'mail' => 'bg-blue-50 text-blue-700',
            default => 'bg-slate-100 text-slate-700',
        };
    }
}
