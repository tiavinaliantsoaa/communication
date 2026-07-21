<?php

namespace App\Providers;

use App\Services\AlerteService;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::setLocale('fr');
        setlocale(LC_TIME, 'fr_FR.UTF-8', 'fr_FR', 'French');

        if ($root = config('app.url')) {
            URL::forceRootUrl(rtrim($root, '/'));
        }

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        View::composer('layouts.partials.header', function ($view) {
            if (! auth()->check()) {
                $view->with('nbAlertes', 0);

                return;
            }

            $data = $view->getData();
            $annee = (int) ($data['annee'] ?? request('annee', now()->year));
            $mois = (int) ($data['mois'] ?? request('mois', now()->month));
            if ($mois < 1 || $mois > 12) {
                $mois = (int) now()->month;
            }

            $view->with([
                'nbAlertes' => app(AlerteService::class)->count($annee, $mois),
                'annee' => $annee,
                'mois' => $mois,
                'moisLabel' => $data['moisLabel'] ?? Carbon::create($annee, $mois, 1)->locale('fr')->isoFormat('MMMM YYYY'),
            ]);
        });
    }
}

