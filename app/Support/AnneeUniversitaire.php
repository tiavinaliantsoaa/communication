<?php

namespace App\Support;

use Carbon\Carbon;

class AnneeUniversitaire
{
    public const FUTURE_COUNT = 3;

    public static function current(?Carbon $date = null): string
    {
        $date ??= Carbon::now();
        $start = $date->month >= 9 ? $date->year : $date->year - 1;

        return $start.'-'.($start + 1);
    }

    /**
     * @return list<string>
     */
    public static function options(?Carbon $date = null): array
    {
        $start = (int) explode('-', self::current($date))[0];
        $years = [];
        for ($i = 0; $i <= self::FUTURE_COUNT; $i++) {
            $years[] = ($start + $i).'-'.($start + $i + 1);
        }

        return $years;
    }

    public static function selected(): string
    {
        $value = session('annee_universitaire');
        if (is_string($value) && in_array($value, self::options(), true)) {
            return $value;
        }

        return self::current();
    }

    public static function select(string $value): void
    {
        if (! in_array($value, self::options(), true)) {
            return;
        }

        session(['annee_universitaire' => $value]);
    }
}
