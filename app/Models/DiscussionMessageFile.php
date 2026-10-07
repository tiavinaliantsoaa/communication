<?php

namespace App\Models;

use App\Support\PrivateFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscussionMessageFile extends Model
{
    protected $fillable = [
        'discussion_message_id',
        'path',
        'nom',
        'mime',
        'taille',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(DiscussionMessage::class, 'discussion_message_id');
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime, 'image/')
            && $this->mime !== 'image/svg+xml';
    }

    public function humanSize(): string
    {
        $bytes = (int) $this->taille;
        if ($bytes < 1024) {
            return $bytes.' o';
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024).' Ko';
        }

        return number_format($bytes / 1048576, 1, ',', ' ').' Mo';
    }

    /**
     * @return array<string, mixed>
     */
    public function present(): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'url' => route('discussions.files.show', $this),
            'image' => $this->isImage(),
            'taille' => $this->humanSize(),
        ];
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $file = static::query()->with('message.group')->where($field ?? $this->getRouteKeyName(), $value)->first();
        $user = auth()->user();

        if (! $file || ! $file->message || ! $user || ! $file->message->group?->isMember($user)) {
            abort(404);
        }

        return $file;
    }

    protected static function booted(): void
    {
        static::deleting(function (DiscussionMessageFile $file) {
            PrivateFile::delete($file->path);
        });
    }
}
