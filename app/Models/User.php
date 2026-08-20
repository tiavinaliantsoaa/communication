<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLES = [
        'super_admin' => 'Super Admin',
        'administrateur' => 'Administrateur',
        'responsable_communication' => 'Responsable Communication',
        'gestionnaire_budget' => 'Gestionnaire Budget',
        'stagiaire' => 'Stagiaire',
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar_path',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function getRoleLabelAttribute(): string
    {
        $fromDb = Role::where('slug', $this->role)->value('name');

        return $fromDb ?: (self::ROLES[$this->role] ?? $this->role);
    }

    public static function roleOptions(): array
    {
        $db = Role::orderBy('name')->pluck('name', 'slug')->all();

        return $db ?: self::ROLES;
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['super_admin', 'administrateur'], true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function canValidateEditorial(): bool
    {
        return $this->isSuperAdmin() || $this->canAccess('calendrier_editorial.validate');
    }

    public function canApproveDepense(): bool
    {
        return $this->isSuperAdmin() || $this->canAccess('depenses.approve');
    }

    /**
     * Check a permission key. Super admin always allowed.
     * Uses direct user permissions (synced from role / customized).
     */
    public function canAccess(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->relationLoaded('permissions')) {
            return $this->permissions->contains('key', $permission);
        }

        return $this->permissions()->where('key', $permission)->exists();
    }

    public function canAccessAny(array $permissions): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        foreach ($permissions as $permission) {
            if ($this->canAccess($permission)) {
                return true;
            }
        }

        return false;
    }

    public function projetCartes(): BelongsToMany
    {
        return $this->belongsToMany(ProjetCarte::class, 'projet_carte_user');
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];
        $initials = collect($parts)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->take(2)->implode('');

        return $initials !== '' ? $initials : '?';
    }

    /**
     * Handle used for @mentions (part before @ in email, lowercased).
     */
    public function getUsernameAttribute(): string
    {
        $local = strstr((string) $this->email, '@', true);

        return strtolower($local !== false ? $local : preg_replace('/\s+/', '', $this->name) ?? 'user');
    }

    public function toMentionArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'avatar_url' => $this->avatar_url,
            'initials' => $this->initials(),
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, self>
     */
    public static function findByMentionHandles(array $handles)
    {
        $handles = collect($handles)
            ->map(fn ($h) => strtolower(ltrim((string) $h, '@')))
            ->filter()
            ->unique()
            ->values();

        if ($handles->isEmpty()) {
            return collect();
        }

        return static::query()
            ->get(['id', 'name', 'email', 'avatar_path'])
            ->filter(fn (self $user) => $handles->contains($user->username))
            ->values();
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        return Storage::disk('public')->url($this->avatar_path);
    }

    protected static function booted(): void
    {
        static::deleting(function (User $user) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }
        });
    }
}

