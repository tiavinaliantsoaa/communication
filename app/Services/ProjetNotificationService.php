<?php

namespace App\Services;

use App\Models\ProjetActivite;
use App\Models\ProjetCarte;
use App\Models\User;
use App\Models\UserNotification;

class ProjetNotificationService
{
    public function __construct(private ActivityLogger $activityLogger)
    {
    }

    public function log(ProjetCarte $carte, ?User $actor, string $message, ?string $titre = null, string $type = 'info'): void
    {
        ProjetActivite::create([
            'projet_carte_id' => $carte->id,
            'user_id' => $actor?->id,
            'message' => $message,
        ]);

        $this->activityLogger->log(
            'projet',
            $message,
            $actor,
            'update',
            $titre ?? 'Gestion de projet',
            route('gestion-projet.index'),
            $carte
        );

        $this->notifyMembres(
            $carte,
            $actor?->id,
            $titre ?? 'Mise à jour projet',
            $message,
            $type
        );
    }

    /**
     * Notify all card members except the actor.
     *
     * @param  array<int>|null  $onlyUserIds  If set, only these users (still excluding actor).
     */
    public function notifyMembres(
        ProjetCarte $carte,
        ?int $actorId,
        string $titre,
        string $description,
        string $type = 'info',
        ?array $onlyUserIds = null
    ): void {
        $carte->loadMissing('membres');

        $recipients = $carte->membres;
        if ($onlyUserIds !== null) {
            $ids = collect($onlyUserIds)->map(fn ($id) => (int) $id);
            $recipients = $recipients->whereIn('id', $ids);
        }

        $url = route('gestion-projet.index');

        foreach ($recipients as $membre) {
            if ($actorId && (int) $membre->id === (int) $actorId) {
                continue;
            }

            UserNotification::create([
                'user_id' => $membre->id,
                'actor_id' => $actorId,
                'projet_carte_id' => $carte->id,
                'type' => $type,
                'titre' => $titre,
                'description' => $description,
                'url' => $url,
            ]);
        }
    }

    public function notifyUsers(iterable $userIds, ?int $actorId, ?int $carteId, string $titre, string $description, string $type = 'info'): void
    {
        $url = route('gestion-projet.index');

        foreach ($userIds as $userId) {
            $userId = (int) $userId;
            if ($actorId && $userId === (int) $actorId) {
                continue;
            }

            UserNotification::create([
                'user_id' => $userId,
                'actor_id' => $actorId,
                'projet_carte_id' => $carteId,
                'type' => $type,
                'titre' => $titre,
                'description' => $description,
                'url' => $url,
            ]);
        }
    }
}
