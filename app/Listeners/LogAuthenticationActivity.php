<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;

class LogAuthenticationActivity
{
    public function __construct(
        private ActivityLogger $logger,
        private Request $request
    ) {}

    public function handleLogin(Login $event): void
    {
        /** @var User|null $user */
        $user = $event->user instanceof User ? $event->user : null;
        if (! $user) {
            return;
        }

        $this->logger->log(
            'auth',
            $user->name.' s’est connecté'
                .($this->request->ip() ? ' (IP '.$this->request->ip().')' : ''),
            $user,
            'login',
            'Connexion',
            null,
            $user
        );
    }

    public function handleLogout(Logout $event): void
    {
        /** @var User|null $user */
        $user = $event->user instanceof User ? $event->user : null;
        if (! $user) {
            return;
        }

        $this->logger->log(
            'auth',
            $user->name.' s’est déconnecté'
                .($this->request->ip() ? ' (IP '.$this->request->ip().')' : ''),
            $user,
            'logout',
            'Déconnexion',
            null,
            $user
        );
    }

    public function handleFailed(Failed $event): void
    {
        $email = (string) ($event->credentials['email'] ?? '');
        /** @var User|null $knownUser */
        $knownUser = $event->user instanceof User
            ? $event->user
            : ($email !== '' ? User::query()->where('email', $email)->first() : null);

        $who = $knownUser?->name
            ?? ($email !== '' ? $email : 'Utilisateur inconnu');

        $log = $this->logger->log(
            'auth',
            'Échec de connexion pour '.$who
                .($this->request->ip() ? ' (IP '.$this->request->ip().')' : ''),
            $knownUser,
            'login_failed',
            'Connexion échouée'
        );

        // Pas d’utilisateur authentifié : rattacher au département connu si possible
        if ($knownUser?->departement_id && ! $log->departement_id) {
            $log->forceFill(['departement_id' => $knownUser->departement_id])->saveQuietly();
        }
    }
}
