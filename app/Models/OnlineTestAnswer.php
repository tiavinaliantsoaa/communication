<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnlineTestAnswer extends Model
{
    protected $fillable = [
        'online_test_invitation_id',
        'online_test_question_id',
        'reponse',
        'points_attribues',
    ];

    protected $casts = [
        'points_attribues' => 'decimal:2',
    ];

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(OnlineTestInvitation::class, 'online_test_invitation_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(OnlineTestQuestion::class, 'online_test_question_id');
    }
}
