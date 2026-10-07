<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DiscussionGroup extends Model
{
    protected $fillable = [
        'departement_id',
        'created_by',
        'nom',
    ];

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'discussion_group_user')
            ->withPivot('last_read_message_id')
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(DiscussionMessage::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(DiscussionMessage::class)->latestOfMany();
    }

    public function isMember(User $user): bool
    {
        if ($this->relationLoaded('members')) {
            return $this->members->contains(fn (User $member) => (int) $member->id === (int) $user->id);
        }

        return $this->members()->where('users.id', $user->id)->exists();
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $group = static::query()->where($field ?? $this->getRouteKeyName(), $value)->first();
        $user = auth()->user();

        if (! $group || ! $user || ! $group->isMember($user)) {
            abort(404);
        }

        return $group;
    }
}
