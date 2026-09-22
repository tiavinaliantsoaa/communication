<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class Departement extends Model
{
    protected $fillable = [
        'nom',
        'slug',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function badgeClasses(): string
    {
        return match ($this->slug) {
            'communication' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
            'commercial' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            'pedagogique' => 'bg-violet-50 text-violet-700 ring-violet-600/20',
            default => 'bg-slate-50 text-slate-700 ring-slate-500/20',
        };
    }

    public static function makeSlug(string $nom): string
    {
        $base = Str::slug($nom);
        if ($base === '') {
            $base = 'departement';
        }

        $slug = $base;
        $i = 2;
        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    public function isInUse(): bool
    {
        if ($this->users()->exists()) {
            return true;
        }

        foreach (DepartementScope::tables() as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'departement_id')) {
                continue;
            }

            if (DB::table($table)->where('departement_id', $this->id)->exists()) {
                return true;
            }
        }

        return false;
    }

    public static function uniqueRule(string $table, string $column, ?int $ignoreId = null): Unique
    {
        $rule = Rule::unique($table, $column);
        $user = auth()->user();
        $deptId = $user?->currentDepartementId();

        if ($user && $user->restrictsDepartementData() && $deptId) {
            $rule->where(fn ($q) => $q->where($table.'.departement_id', $deptId));
        }

        if ($ignoreId) {
            $rule->ignore($ignoreId);
        }

        return $rule;
    }

    /**
     * Attach rows created without a department (seeders) to Communication.
     */
    public static function backfillUnassigned(): void
    {
        if (! Schema::hasTable('departements')) {
            return;
        }

        $departementId = static::query()->where('slug', 'communication')->value('id');
        if (! $departementId) {
            return;
        }

        if (Schema::hasColumn('users', 'departement_id')) {
            DB::table('users')->whereNull('departement_id')->update([
                'departement_id' => $departementId,
            ]);
        }

        foreach (DepartementScope::tables() as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'departement_id')) {
                continue;
            }

            DB::table($table)->whereNull('departement_id')->update([
                'departement_id' => $departementId,
            ]);
        }
    }
}
