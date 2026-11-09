<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class OnlineTestInvitation extends Model
{
    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_EN_COURS = 'en_cours';
    public const STATUT_TERMINE = 'termine';
    public const STATUT_ANNULE = 'annule';
    public const STATUT_EXPIRE = 'expire';

    public const STATUTS = [
        self::STATUT_EN_ATTENTE => 'En attente',
        self::STATUT_EN_COURS => 'En cours',
        self::STATUT_TERMINE => 'Terminé',
        self::STATUT_ANNULE => 'Annulé',
        self::STATUT_EXPIRE => 'Expiré',
    ];

    protected $fillable = [
        'online_test_id',
        'crm_candidate_id',
        'created_by',
        'token',
        'candidat_nom',
        'candidat_email',
        'statut',
        'opened_at',
        'started_at',
        'ends_at',
        'submitted_at',
        'cancelled_at',
        'cancel_reason',
        'score_auto',
        'score_final',
        'valide',
        'validated_by',
        'validated_at',
        'commentaire_correcteur',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'started_at' => 'datetime',
        'ends_at' => 'datetime',
        'submitted_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'validated_at' => 'datetime',
        'score_auto' => 'decimal:2',
        'score_final' => 'decimal:2',
        'valide' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Invitations have no departement_id of their own. Staff queries must
        // follow the parent test, which is already department-scoped. Public
        // candidates are unauthenticated, so this scope does not apply to them.
        static::addGlobalScope('departement', function (Builder $builder) {
            $user = auth()->user();
            if (! $user || ! $user->restrictsDepartementData()) {
                return;
            }

            $builder->whereHas('test');
        });
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(OnlineTest::class, 'online_test_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(CrmCandidate::class, 'crm_candidate_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(OnlineTestAnswer::class);
    }

    public static function generateToken(): string
    {
        do {
            $token = Str::random(48);
        } while (static::withoutGlobalScope('departement')->where('token', $token)->exists());

        return $token;
    }

    public function publicUrl(): string
    {
        return route('online-tests.take.show', $this->token);
    }

    public function getStatutLabelAttribute(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    public function getStatutColorAttribute(): string
    {
        return match ($this->statut) {
            self::STATUT_EN_ATTENTE => 'bg-slate-100 text-slate-700 ring-slate-500/20',
            self::STATUT_EN_COURS => 'bg-blue-50 text-blue-700 ring-blue-600/20',
            self::STATUT_TERMINE => 'bg-green-50 text-green-700 ring-green-600/20',
            self::STATUT_ANNULE => 'bg-red-50 text-red-700 ring-red-600/20',
            self::STATUT_EXPIRE => 'bg-amber-50 text-amber-700 ring-amber-600/20',
            default => 'bg-slate-50 text-slate-700 ring-slate-600/20',
        };
    }

    public function isUsable(): bool
    {
        return in_array($this->statut, [self::STATUT_EN_ATTENTE, self::STATUT_EN_COURS], true);
    }

    public function remainingSeconds(): int
    {
        if (! $this->ends_at || $this->statut !== self::STATUT_EN_COURS) {
            return 0;
        }

        return max(0, now()->diffInSeconds($this->ends_at, false));
    }

    public function markExpiredIfNeeded(): bool
    {
        if ($this->statut === self::STATUT_EN_COURS && $this->ends_at && $this->ends_at->isPast()) {
            $this->update(['statut' => self::STATUT_EXPIRE]);

            return true;
        }

        return false;
    }
}
