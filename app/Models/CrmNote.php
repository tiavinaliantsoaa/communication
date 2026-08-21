<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmNote extends Model
{
    protected $fillable = [
        'crm_candidate_id',
        'user_id',
        'content',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(CrmCandidate::class, 'crm_candidate_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
