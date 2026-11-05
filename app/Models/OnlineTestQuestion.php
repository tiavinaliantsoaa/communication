<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnlineTestQuestion extends Model
{
    public const TYPES = [
        'qcm' => 'QCM (choix unique)',
        'texte' => 'Réponse libre',
    ];

    protected $fillable = [
        'online_test_id',
        'enonce',
        'type',
        'options',
        'bonne_reponse',
        'points',
        'position',
    ];

    protected $casts = [
        'options' => 'array',
        'points' => 'decimal:2',
        'position' => 'integer',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(OnlineTest::class, 'online_test_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(OnlineTestAnswer::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function optionsList(): array
    {
        $options = $this->options ?? [];

        return array_values(array_filter(array_map(
            fn ($o) => is_string($o) ? trim($o) : '',
            is_array($options) ? $options : []
        )));
    }
}
