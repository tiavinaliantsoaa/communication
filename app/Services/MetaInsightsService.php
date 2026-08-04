<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MetaInsightsService
{
    protected string $version;

    protected ?string $pageId;

    protected ?string $token;

    protected ?string $igUserId;

    protected int $cacheTtl;

    public function __construct()
    {
        $this->version = (string) config('services.meta.graph_version', 'v21.0');
        $this->pageId = config('services.meta.page_id') ?: null;
        $this->token = config('services.meta.page_access_token') ?: null;
        $this->igUserId = config('services.meta.ig_user_id') ?: null;
        $this->cacheTtl = (int) config('services.meta.cache_ttl', 1800);
    }

    public function isConfigured(): bool
    {
        return filled($this->pageId) && filled($this->token);
    }

    /**
     * @return array{
     *     api_connected: bool,
     *     error: ?string,
     *     kpis: array,
     *     topPosts: array,
     *     engagementMensuel: array
     * }
     */
    public function dashboard(): array
    {
        if (! $this->isConfigured()) {
            return $this->demoPayload(
                'Renseignez META_PAGE_ID et META_PAGE_ACCESS_TOKEN dans le fichier .env, puis videz le cache config (php artisan config:clear).'
            );
        }

        try {
            return Cache::remember('meta.insights.dashboard', $this->cacheTtl, function () {
                return $this->fetchDashboard();
            });
        } catch (\Throwable $e) {
            Log::warning('Meta Insights: '.$e->getMessage());

            return $this->demoPayload($e->getMessage());
        }
    }

    protected function fetchDashboard(): array
    {
        $page = $this->get($this->pageId, [
            'fields' => 'name,fan_count,followers_count,instagram_business_account',
        ]);

        $followersFb = (int) ($page['followers_count'] ?? $page['fan_count'] ?? 0);
        $igUserId = ltrim((string) ($this->igUserId ?: data_get($page, 'instagram_business_account.id') ?: ''), '=');

        $warnings = [];
        $followersIg = 0;
        $igPosts = [];

        if ($igUserId !== '') {
            try {
                $ig = $this->get($igUserId, [
                    'fields' => 'followers_count,media_count,username',
                ]);
                $followersIg = (int) ($ig['followers_count'] ?? 0);
                $igPosts = $this->fetchInstagramPosts($igUserId, $followersIg);
            } catch (\Throwable $e) {
                Log::warning('Meta Insights IG: '.$e->getMessage());
                $warnings[] = 'Instagram limité : ajoutez la permission instagram_basic (et régénérez un token Page).';
            }
        }

        $fbPosts = $this->fetchFacebookPosts($followersFb);
        if ($fbPosts === [] && $this->lastEngagementDenied) {
            $warnings[] = 'Engagement Facebook limité : ajoutez pages_read_user_content puis régénérez le token Page.';
        }

        $allPosts = collect($fbPosts)->merge($igPosts)
            ->sortByDesc(fn (array $p) => [$p['likes'] + $p['commentaires'] + $p['partages'], $p['date']])
            ->take(10)
            ->values()
            ->all();

        $engagementMensuel = $this->buildMonthlyEngagement($fbPosts, $igPosts);

        $postsThisMonth = collect($fbPosts)->merge($igPosts)
            ->filter(fn (array $p) => Carbon::parse($p['date'])->isCurrentMonth())
            ->count();

        $avgEngagement = collect($allPosts)->avg('engagement');
        $avgEngagement = $avgEngagement !== null ? round((float) $avgEngagement, 1) : 0.0;

        return [
            'api_connected' => true,
            'error' => $warnings === [] ? null : implode(' ', $warnings),
            'kpis' => [
                'followers_fb' => $followersFb,
                'followers_ig' => $followersIg,
                'engagement_moyen' => $avgEngagement,
                'posts_mois' => $postsThisMonth,
                'api_connected' => true,
            ],
            'topPosts' => $allPosts,
            'engagementMensuel' => $engagementMensuel,
        ];
    }

    protected bool $lastEngagementDenied = false;

    protected function fetchFacebookPosts(int $followers): array
    {
        $this->lastEngagementDenied = false;

        // likes/comments need pages_read_user_content — try rich fields, then fallback.
        try {
            $response = $this->get($this->pageId.'/posts', [
                'fields' => 'message,created_time,shares,reactions.summary(true),comments.summary(true)',
                'limit' => 50,
            ]);
        } catch (\Throwable $e) {
            $this->lastEngagementDenied = true;
            Log::info('Meta Insights FB engagement fields unavailable, fallback: '.$e->getMessage());
            $response = $this->get($this->pageId.'/posts', [
                'fields' => 'message,created_time,shares',
                'limit' => 50,
            ]);
        }

        $posts = [];
        foreach ($response['data'] ?? [] as $item) {
            $likes = (int) (
                data_get($item, 'reactions.summary.total_count')
                ?? data_get($item, 'likes.summary.total_count')
                ?? 0
            );
            $comments = (int) data_get($item, 'comments.summary.total_count', 0);
            $shares = (int) data_get($item, 'shares.count', 0);
            $interactions = $likes + $comments + $shares;
            $engagement = $followers > 0
                ? round(($interactions / $followers) * 100, 1)
                : 0.0;

            $message = trim((string) ($item['message'] ?? ''));
            $titre = $message !== ''
                ? Str::limit($message, 60)
                : 'Publication Facebook';

            $posts[] = [
                'reseau' => 'Facebook',
                'titre' => $titre,
                'likes' => $likes,
                'commentaires' => $comments,
                'partages' => $shares,
                'engagement' => $engagement,
                'date' => Carbon::parse($item['created_time'])->toDateString(),
            ];
        }

        return $posts;
    }

    protected function fetchInstagramPosts(string $igUserId, int $followers): array
    {
        $response = $this->get($igUserId.'/media', [
            'fields' => 'caption,timestamp,like_count,comments_count,media_type,permalink',
            'limit' => 50,
        ]);

        $posts = [];
        foreach ($response['data'] ?? [] as $item) {
            $likes = (int) ($item['like_count'] ?? 0);
            $comments = (int) ($item['comments_count'] ?? 0);
            $interactions = $likes + $comments;
            $engagement = $followers > 0
                ? round(($interactions / $followers) * 100, 1)
                : 0.0;

            $caption = trim((string) ($item['caption'] ?? ''));
            $type = $item['media_type'] ?? 'IMAGE';
            $titre = $caption !== ''
                ? Str::limit($caption, 60)
                : 'Publication Instagram ('.$type.')';

            $posts[] = [
                'reseau' => 'Instagram',
                'titre' => $titre,
                'likes' => $likes,
                'commentaires' => $comments,
                'partages' => 0,
                'engagement' => $engagement,
                'date' => Carbon::parse($item['timestamp'])->toDateString(),
            ];
        }

        return $posts;
    }

    /**
     * Average post engagement rate per month for the last 7 months.
     *
     * @param  array<int, array>  $fbPosts
     * @param  array<int, array>  $igPosts
     */
    protected function buildMonthlyEngagement(array $fbPosts, array $igPosts): array
    {
        $labels = [];
        $facebook = [];
        $instagram = [];

        for ($i = 6; $i >= 0; $i--) {
            $month = now()->copy()->startOfMonth()->subMonths($i);
            $labels[] = $month->locale('fr')->isoFormat('MMM');

            $facebook[] = $this->avgEngagementForMonth($fbPosts, $month);
            $instagram[] = $this->avgEngagementForMonth($igPosts, $month);
        }

        return [
            'labels' => $labels,
            'facebook' => $facebook,
            'instagram' => $instagram,
        ];
    }

    /**
     * @param  array<int, array>  $posts
     */
    protected function avgEngagementForMonth(array $posts, Carbon $month): float
    {
        $values = collect($posts)
            ->filter(function (array $post) use ($month) {
                $date = Carbon::parse($post['date']);

                return $date->year === $month->year && $date->month === $month->month;
            })
            ->pluck('engagement');

        if ($values->isEmpty()) {
            return 0.0;
        }

        return round((float) $values->avg(), 1);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    protected function get(string $path, array $query = []): array
    {
        $url = sprintf('https://graph.facebook.com/%s/%s', $this->version, ltrim($path, '/'));

        $response = Http::timeout(20)
            ->acceptJson()
            ->get($url, array_merge($query, [
                'access_token' => $this->token,
            ]));

        if ($response->failed()) {
            $message = data_get($response->json(), 'error.message')
                ?? ('Erreur Meta Graph API HTTP '.$response->status());

            throw new \RuntimeException($message);
        }

        return $response->json() ?? [];
    }

    /**
     * @return array{
     *     api_connected: bool,
     *     error: ?string,
     *     kpis: array,
     *     topPosts: array,
     *     engagementMensuel: array
     * }
     */
    protected function demoPayload(?string $error = null): array
    {
        return [
            'api_connected' => false,
            'error' => $error,
            'kpis' => [
                'followers_fb' => 12400,
                'followers_ig' => 18650,
                'engagement_moyen' => 6.4,
                'posts_mois' => 28,
                'api_connected' => false,
            ],
            'topPosts' => [
                [
                    'reseau' => 'Instagram',
                    'titre' => 'Reel — Vie étudiante campus',
                    'likes' => 1240,
                    'commentaires' => 86,
                    'partages' => 42,
                    'engagement' => 8.4,
                    'date' => now()->subDays(7)->toDateString(),
                ],
                [
                    'reseau' => 'Facebook',
                    'titre' => 'Post Portes Ouvertes ESCM',
                    'likes' => 980,
                    'commentaires' => 64,
                    'partages' => 71,
                    'engagement' => 6.9,
                    'date' => now()->subDays(4)->toDateString(),
                ],
                [
                    'reseau' => 'Instagram',
                    'titre' => 'Carrousel Bachelor Digital',
                    'likes' => 870,
                    'commentaires' => 41,
                    'partages' => 28,
                    'engagement' => 5.7,
                    'date' => now()->subDays(11)->toDateString(),
                ],
                [
                    'reseau' => 'Facebook',
                    'titre' => 'Témoignage alumni MBA',
                    'likes' => 720,
                    'commentaires' => 53,
                    'partages' => 35,
                    'engagement' => 5.1,
                    'date' => now()->subDays(19)->toDateString(),
                ],
            ],
            'engagementMensuel' => [
                'labels' => ['Janv.', 'Févr.', 'Mars', 'Avr.', 'Mai', 'Juin', 'Juil.'],
                'facebook' => [3.2, 3.8, 4.1, 3.9, 4.5, 5.2, 5.8],
                'instagram' => [4.1, 4.6, 5.0, 5.4, 6.1, 6.8, 7.2],
            ],
        ];
    }
}
