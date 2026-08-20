<?php

namespace App\Services;

use Illuminate\Http\Request;

class VisitorInfoService
{
    public function fromRequest(Request $request, bool $fast = false): array
    {
        $ua = (string) $request->userAgent();
        $ip = $request->ip();

        $geo = $fast
            ? $this->geoFromHeaders($request)
            : array_merge($this->geoFromHeaders($request), $this->lookupGeo($ip));

        return [
            'ip' => $ip,
            'country' => $geo['country'] ?? null,
            'city' => $geo['city'] ?? null,
            'device' => $this->detectDevice($ua),
            'os' => $this->detectOs($ua),
            'browser' => $this->detectBrowser($ua),
            'referer' => $this->truncate($request->headers->get('referer'), 2000),
            'user_agent' => $this->truncate($ua, 1000),
            'created_at' => now(),
        ];
    }

    /**
     * Instant geo from CDN / proxy headers (no external HTTP).
     *
     * @return array{country?:string,city?:string}
     */
    private function geoFromHeaders(Request $request): array
    {
        $country = $request->headers->get('CF-IPCountry')
            ?? $request->headers->get('X-Country-Code')
            ?? null;

        if ($country && strtoupper($country) === 'XX') {
            $country = null;
        }

        return array_filter([
            'country' => $country,
            'city' => $request->headers->get('X-City'),
        ]);
    }

    private function detectDevice(string $ua): string
    {
        $ua = strtolower($ua);
        if (preg_match('/ipad|tablet|kindle|playbook|silk|(android(?!.*mobile))/i', $ua)) {
            return 'Tablet';
        }
        if (preg_match('/mobi|iphone|ipod|android.*mobile|windows phone|blackberry/i', $ua)) {
            return 'Mobile';
        }

        return 'Desktop';
    }

    private function detectOs(string $ua): string
    {
        $map = [
            'Windows' => '/windows nt/i',
            'macOS' => '/mac os x|macintosh/i',
            'iOS' => '/iphone|ipad|ipod/i',
            'Android' => '/android/i',
            'Linux' => '/linux/i',
            'Chrome OS' => '/cros/i',
        ];
        foreach ($map as $label => $pattern) {
            if (preg_match($pattern, $ua)) {
                return $label;
            }
        }

        return 'Autre';
    }

    private function detectBrowser(string $ua): string
    {
        $map = [
            'Edge' => '/edg\//i',
            'Opera' => '/opr\/|opera/i',
            'Chrome' => '/chrome\//i',
            'Firefox' => '/firefox\//i',
            'Safari' => '/safari\//i',
            'Samsung Internet' => '/samsungbrowser/i',
            'IE' => '/msie|trident/i',
        ];
        foreach ($map as $label => $pattern) {
            if (preg_match($pattern, $ua)) {
                // Chrome also matches Safari string — Chrome checked before Safari
                if ($label === 'Safari' && preg_match('/chrome\//i', $ua)) {
                    continue;
                }

                return $label;
            }
        }

        return 'Autre';
    }

    /**
     * Best-effort geo lookup (ip-api.com free, non-commercial). Fail silently.
     *
     * @return array{country?:string,city?:string}
     */
    private function lookupGeo(?string $ip): array
    {
        if (! $ip || in_array($ip, ['127.0.0.1', '::1'], true) || ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return [];
        }

        try {
            $ctx = stream_context_create(['http' => ['timeout' => 0.6]]);
            $json = @file_get_contents('http://ip-api.com/json/'.urlencode($ip).'?fields=status,country,city', false, $ctx);
            if (! $json) {
                return [];
            }
            $data = json_decode($json, true);
            if (($data['status'] ?? '') !== 'success') {
                return [];
            }

            return [
                'country' => $data['country'] ?? null,
                'city' => $data['city'] ?? null,
            ];
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function truncate(?string $value, int $max): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return mb_strlen($value) > $max ? mb_substr($value, 0, $max) : $value;
    }
}
