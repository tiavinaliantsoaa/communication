<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * @return array<int, array{title: string, heading: string, message: string}>
     */
    public static function pages(): array
    {
        return [
            401 => [
                'title' => 'Connexion requise',
                'heading' => 'Connexion requise',
                'message' => 'Connectez-vous pour accéder à cette page.',
            ],
            403 => [
                'title' => 'Accès refusé',
                'heading' => 'Accès refusé',
                'message' => 'Vous n’avez pas l’autorisation d’ouvrir cette page.',
            ],
            404 => [
                'title' => 'Page introuvable',
                'heading' => 'Page introuvable',
                'message' => 'La page que vous recherchez n’existe pas ou a été déplacée.',
            ],
            419 => [
                'title' => 'Session expirée',
                'heading' => 'Session expirée',
                'message' => 'Votre session a expiré. Revenez en arrière, rechargez la page, puis réessayez.',
            ],
            429 => [
                'title' => 'Trop de tentatives',
                'heading' => 'Trop de tentatives',
                'message' => 'Vous avez fait trop de demandes en peu de temps. Patientez un instant, puis réessayez.',
            ],
            500 => [
                'title' => 'Erreur du serveur',
                'heading' => 'Erreur du serveur',
                'message' => 'Une erreur inattendue s’est produite. Réessayez dans un moment.',
            ],
            503 => [
                'title' => 'Service indisponible',
                'heading' => 'Service indisponible',
                'message' => 'L’application est momentanément indisponible. Réessayez un peu plus tard.',
            ],
        ];
    }

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e): Response
    {
        $response = parent::render($request, $e);

        if (! $request instanceof Request || $request->expectsJson()) {
            return $response;
        }

        $status = $response->getStatusCode();
        $meta = self::pages()[$status] ?? null;
        if ($meta === null) {
            return $response;
        }

        $loggedIn = false;
        try {
            $loggedIn = auth()->check();
        } catch (Throwable) {
            $loggedIn = false;
        }

        $data = [
            'title' => $meta['title'],
            'code' => $status,
            'heading' => $meta['heading'],
            'message' => $meta['message'],
        ];

        if ($loggedIn) {
            try {
                return response()->view('errors.authenticated', $data, $status);
            } catch (Throwable) {
                // La barre latérale n’a pas pu s’afficher : page autonome.
            }
        }

        return response()->view('errors.guest', $data, $status);
    }
}
