<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DiscussionMessage extends Model
{
    protected $fillable = [
        'discussion_group_id',
        'user_id',
        'reply_to_id',
        'body',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(DiscussionGroup::class, 'discussion_group_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(DiscussionMessageFile::class);
    }

    public function excerpt(): string
    {
        $text = trim(preg_replace('/\s+/', ' ', (string) $this->body) ?? '');
        if ($text !== '' && ! $this->is_system) {
            return Str::limit($text, 80);
        }

        $file = $this->relationLoaded('files') ? $this->files->first() : $this->files()->first();
        if ($file) {
            return $file->isImage() ? 'Photo' : $file->nom;
        }

        return Str::limit($text, 80);
    }

    /**
     * @return array<string, mixed>
     */
    public function presentFor(User $viewer): array
    {
        $reply = null;
        if ($this->replyTo) {
            $reply = [
                'id' => $this->replyTo->id,
                'author' => $this->replyTo->is_system ? 'Discussion' : ($this->replyTo->user?->name ?? 'Membre'),
                'excerpt' => $this->replyTo->excerpt(),
                'color' => $this->replyTo->user?->chatColor() ?? '#667781',
            ];
        }

        $created = $this->created_at;

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'author' => $this->user?->name ?? 'Discussion',
            'initials' => $this->user?->initials() ?? '•',
            'avatar_url' => $this->user?->avatar_url,
            'color' => $this->user?->chatColor() ?? '#667781',
            'body' => $this->body,
            'excerpt' => $this->excerpt(),
            'mine' => ! $this->is_system && (int) $this->user_id === (int) $viewer->id,
            'system' => (bool) $this->is_system,
            'time' => $created?->timezone(config('app.timezone'))->format('H:i'),
            'day' => $created?->toDateString(),
            'day_label' => self::dayLabel($created),
            'reply' => $reply,
            'files' => $this->files->map(fn (DiscussionMessageFile $file) => $file->present())->values()->all(),
        ];
    }

    public static function dayLabel($date): string
    {
        if (! $date) {
            return '';
        }

        if ($date->isToday()) {
            return "Aujourd'hui";
        }

        if ($date->isYesterday()) {
            return 'Hier';
        }

        return $date->locale('fr')->isoFormat('D MMMM YYYY');
    }
}
