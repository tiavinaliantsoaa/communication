<?php

if (! function_exists('format_ar')) {
    function format_ar(float|int $amount): string
    {
        return number_format($amount, 0, ',', ' ') . ' Ar';
    }
}

if (! function_exists('format_ar_short')) {
    /**
     * Compact amount with K / M / T suffixes.
     * K = milliers, M = millions, T = billions (10^12).
     */
    function format_ar_short(float|int $amount, bool withCurrency = true): string
    {
        $abs = abs((float) $amount);
        $sign = $amount < 0 ? '-' : '';

        if ($abs >= 1_000_000_000_000) {
            $value = $abs / 1_000_000_000_000;
            $suffix = 'T';
        } elseif ($abs >= 1_000_000) {
            $value = $abs / 1_000_000;
            $suffix = 'M';
        } elseif ($abs >= 1_000) {
            $value = $abs / 1_000;
            $suffix = 'K';
        } else {
            $formatted = $sign.number_format($abs, 0, ',', ' ');

            return $withCurrency ? $formatted.' Ar' : $formatted;
        }

        $decimals = $value >= 100 ? 0 : ($value >= 10 ? 1 : 2);
        $compact = rtrim(rtrim(number_format($value, $decimals, '.', ''), '0'), '.');

        $formatted = $sign.$compact.$suffix;

        return $withCurrency ? $formatted.' Ar' : $formatted;
    }
}
