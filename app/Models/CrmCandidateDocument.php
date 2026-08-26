<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CrmCandidateDocument extends Model
{
    protected $fillable = [
        'crm_candidate_id',
        'crm_document_type_id',
        'path',
        'original_name',
        'mime',
        'size',
        'uploaded_by',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(CrmCandidate::class, 'crm_candidate_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(CrmDocumentType::class, 'crm_document_type_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): ?string
    {
        if (! $this->path) {
            return null;
        }

        return Storage::disk('public')->url($this->path);
    }

    protected static function booted(): void
    {
        static::deleting(function (self $doc) {
            if ($doc->path) {
                Storage::disk('public')->delete($doc->path);
            }
        });
    }
}
