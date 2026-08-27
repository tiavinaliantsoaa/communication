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
        'external_url',
        'glide_document_id',
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
        if (filled($this->path)) {
            return Storage::disk('public')->url($this->path);
        }

        if (filled($this->external_url)) {
            return $this->external_url;
        }

        return null;
    }

    public function isExternal(): bool
    {
        return filled($this->external_url) && ! filled($this->path);
    }

    protected static function booted(): void
    {
        static::deleting(function (self $doc) {
            if (filled($doc->path)) {
                Storage::disk('public')->delete($doc->path);
            }
        });
    }
}
