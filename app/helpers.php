<?php

if (! function_exists('format_ar')) {
    function format_ar(float|int $amount): string
    {
        $formatted = number_format(abs((float) $amount), 0, ',', ' ');

        return ((float) $amount < 0 ? '- ' : '').$formatted.' Ar';
    }
}

if (! function_exists('format_date')) {
    /**
     * Affichage date JJ/MM/AAAA (D/M/Y).
     */
    function format_date(mixed $date, string $fallback = '—'): string
    {
        if (blank($date)) {
            return $fallback;
        }

        try {
            return \Carbon\Carbon::parse($date)->format('d/m/Y');
        } catch (\Throwable) {
            return $fallback;
        }
    }
}

if (! function_exists('format_ar_short')) {
    /**
     * Compact amount with K / M / T suffixes.
     * K = milliers, M = millions, T = trillions (10^12).
     */
    function format_ar_short(float|int $amount, bool $withCurrency = true): string
    {
        $abs = abs((float) $amount);
        $sign = $amount < 0 ? '-' : '';

        if ($abs >= 1000000000000) {
            $value = $abs / 1000000000000;
            $suffix = 'T';
        } elseif ($abs >= 1000000) {
            $value = $abs / 1000000;
            $suffix = 'M';
        } elseif ($abs >= 1000) {
            $value = $abs / 1000;
            $suffix = 'K';
        } else {
            $formatted = $sign.number_format($abs, 0, ',', ' ');

            return $withCurrency ? $formatted.' Ar' : $formatted;
        }

        $decimals = $value >= 100 ? 0 : ($value >= 10 ? 1 : 2);
        $compact = number_format($value, $decimals, '.', '');
        if (str_contains($compact, '.')) {
            $compact = rtrim(rtrim($compact, '0'), '.');
        }

        $formatted = $sign.$compact.$suffix;

        return $withCurrency ? $formatted.' Ar' : $formatted;
    }
}

if (! function_exists('app_subdirectory_prefix')) {
    /**
     * Préfixe URL quand l’app est servie sous /communication (OVH).
     * Vide en local (php artisan serve) ou si la requête n’est pas préfixée.
     */
    function app_subdirectory_prefix(): string
    {
        $prefix = '/communication';
        $requestPath = parse_url(request()->getRequestUri() ?: '/', PHP_URL_PATH) ?: '/';

        if (strncasecmp($requestPath, $prefix.'/', strlen($prefix) + 1) === 0
            || strcasecmp(rtrim($requestPath, '/'), $prefix) === 0) {
            return $prefix;
        }

        return '';
    }
}

if (! function_exists('livewire_frontend_scripts')) {
    /**
     * Scripts Livewire avec URLs compatibles sous-dossier (/communication).
     */
    function livewire_frontend_scripts(): string
    {
        $html = \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts();
        $prefix = app_subdirectory_prefix();
        if ($prefix === '') {
            return $html;
        }

        return str_replace(
            [
                'src="/livewire/',
                'data-update-uri="/livewire/',
            ],
            [
                'src="'.$prefix.'/livewire/',
                'data-update-uri="'.$prefix.'/livewire/',
            ],
            $html
        );
    }
}
