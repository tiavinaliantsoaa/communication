<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class Periode
{
    /**
     * @return array{0: int, 1: int}
     */
    public static function remember(Request $request): array
    {
        if ($request->has('annee') || $request->has('mois')) {
            [$annee, $mois] = self::normalize(
                (int) $request->input('annee', session('periode.annee', now()->year)),
                (int) $request->input('mois', session('periode.mois', now()->month))
            );
            session([
                'periode.annee' => $annee,
                'periode.mois' => $mois,
            ]);
        }

        return self::current();
    }

    /**
     * @return array{0: int, 1: int}
     */
    public static function current(): array
    {
        return self::normalize(
            (int) session('periode.annee', now()->year),
            (int) session('periode.mois', now()->month)
        );
    }

    public static function label(): string
    {
        [$annee, $mois] = self::current();

        return Carbon::create($annee, $mois, 1)->locale('fr')->isoFormat('MMMM YYYY');
    }

    public static function whereMonth(Builder $query, string $column): Builder
    {
        [$annee, $mois] = self::current();

        return $query->whereYear($column, $annee)->whereMonth($column, $mois);
    }

    public static function whereOverlaps(Builder $query, string $start = 'date_debut', string $end = 'date_fin'): Builder
    {
        [$annee, $mois] = self::current();
        $from = Carbon::create($annee, $mois, 1)->startOfMonth()->toDateString();
        $to = Carbon::create($annee, $mois, 1)->endOfMonth()->toDateString();

        return $query
            ->whereDate($start, '<=', $to)
            ->where(function (Builder $inner) use ($start, $end, $from) {
                $inner->whereDate($end, '>=', $from)
                    ->orWhere(function (Builder $open) use ($start, $end, $from) {
                        $open->whereNull($end)->whereDate($start, '>=', $from);
                    });
            });
    }

    /**
     * @return array{0: int, 1: int}
     */
    private static function normalize(int $annee, int $mois): array
    {
        if ($mois < 1 || $mois > 12) {
            $mois = (int) now()->month;
        }
        if ($annee < 2020 || $annee > 2100) {
            $annee = (int) now()->year;
        }

        return [$annee, $mois];
    }
}
