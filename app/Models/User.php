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

    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
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
        return $this->isSuperAdmin();
    }

    public function canApproveDepense(): bool
    {
        return $this->isSuperAdmin();
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

