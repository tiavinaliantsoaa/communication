<?php

namespace App\Providers;

use App\Models\Departement;
use App\Models\DepartementScope;
use App\Models\UserNotification;
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

        foreach (DepartementScope::models() as $model) {
            $model::addGlobalScope('departement', new DepartementScope);
            $model::creating(function ($record) {
                if ($record->departement_id) {
                    return;
                }

                $departementId = auth()->user()?->currentDepartementId();
                if ($departementId) {
                    $record->departement_id = $departementId;
                }
            });
        }

        View::composer('layouts.app', function () {
            $actif = null;
            $liste = collect();

            if ($user = auth()->user()) {
                if ($user->isSuperAdmin()) {
                    $liste = Departement::query()->orderBy('nom')->get();
                    $selected = session('departement_actif_id');
                    if ($selected && ! $liste->contains(fn ($departement) => (int) $departement->id === (int) $selected)) {
                        session()->forget('departement_actif_id');
                    }
                }

                $actifId = $user->currentDepartementId();
                $actif = $liste->firstWhere('id', $actifId);
                if (! $actif && $actifId) {
                    $actif = Departement::query()->find($actifId);
                }
            }

            View::share('departementActif', $actif);
            View::share('departementsListe', $liste);
        });

        View::composer('layouts.partials.header', function ($view) {
            if (! auth()->check()) {
                $view->with([
                    'nbAlertes' => 0,
                    'nbUnreadNotifications' => 0,
                    'latestNotificationId' => 0,
                ]);

                return;
            }

            $data = $view->getData();
            $annee = (int) ($data['annee'] ?? request('annee', now()->year));
            $mois = (int) ($data['mois'] ?? request('mois', now()->month));
            if ($mois < 1 || $mois > 12) {
                $mois = (int) now()->month;
            }

            $userId = auth()->id();
            $nbUnread = UserNotification::where('user_id', $userId)->unread()->count();
            $latestId = (int) (UserNotification::where('user_id', $userId)->max('id') ?? 0);

            $view->with([
                'nbAlertes' => app(AlerteService::class)->count($annee, $mois),
                'nbUnreadNotifications' => $nbUnread,
                'latestNotificationId' => $latestId,
                'annee' => $annee,
                'mois' => $mois,
                'moisLabel' => $data['moisLabel'] ?? Carbon::create($annee, $mois, 1)->locale('fr')->isoFormat('MMMM YYYY'),
            ]);
        });
        View::composer('layouts.partials.sidebar', function () {
            if (! auth()->check()) {
                return;
            }

            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('permissions')) {
                    $user = auth()->user();
                    if (! $user->relationLoaded('permissions')) {
                        if (! $user->permissions()->exists()) {
                            \App\Services\AccessService::bootstrap();
                        }
                        $user->load('permissions');
                    }
                }
            } catch (\Throwable $e) {
                // ignore during early migrate
            }
        });
    }
}
