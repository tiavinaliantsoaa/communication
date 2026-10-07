<?php

namespace App\Services;

use App\Models\DiscussionGroup;
use App\Models\DiscussionMessage;
use App\Models\DiscussionMessageFile;
use App\Models\User;
use App\Support\PrivateFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DiscussionService
{
    /**
     * @return array<string, mixed>
     */
    public function page(User $user, ?int $groupId): array
    {
        $active = null;
        $thread = $this->emptyThread();

        if ($groupId) {
            $group = DiscussionGroup::query()->find($groupId);
            if ($group && $group->isMember($user)) {
                $active = $this->identity($group);
                $thread = $this->thread($user, $group, true);
            }
        }

        $departementId = $user->currentDepartementId();

        return [
            'me' => $user->id,
            'groups' => $this->summaries($user),
            'active' => $active,
            'messages' => $thread['messages'],
            'members' => $thread['members'],
            'directory' => $thread['directory'],
            'annuaire' => $departementId ? $this->people($departementId, [$user->id]) : [],
            'has_more' => $thread['has_more'],
            'latest_id' => $thread['latest_id'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function poll(User $user, ?int $groupId, int $since): array
    {
        $messages = [];
        $latestId = $since;

        if ($groupId) {
            $group = DiscussionGroup::query()->find($groupId);
            if (! $group || ! $group->isMember($user)) {
                abort(404);
            }

            $query = $group->messages()
                ->with(['user', 'files', 'replyTo.user', 'replyTo.files'])
                ->orderBy('id');

            if ($since > 0) {
                $query->where('id', '>', $since);
            }

            $fresh = $query->limit(100)->get();
            $messages = $fresh->map(fn (DiscussionMessage $message) => $message->presentFor($user))->values()->all();
            $latestId = (int) ($fresh->max('id') ?: $since);
            $this->markRead($group, $user);
        }

        return [
            'ok' => true,
            'unread' => $this->unreadTotal($user),
            'groups' => $this->summaries($user),
            'messages' => $messages,
            'latest_id' => $latestId,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function open(User $user, DiscussionGroup $group): array
    {
        return array_merge(['ok' => true, 'group' => $this->identity($group)], $this->thread($user, $group, true));
    }

    /**
     * @return array<string, mixed>
     */
    public function older(User $user, DiscussionGroup $group, int $before): array
    {
        $messages = $group->messages()
            ->with(['user', 'files', 'replyTo.user', 'replyTo.files'])
            ->where('id', '<', $before)
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->sortBy('id')
            ->values();

        $oldest = $messages->first();

        return [
            'ok' => true,
            'messages' => $messages->map(fn (DiscussionMessage $message) => $message->presentFor($user))->values()->all(),
            'has_more' => $oldest
                ? $group->messages()->where('id', '<', $oldest->id)->exists()
                : false,
        ];
    }

    /**
     * @param  list<int>  $memberIds
     */
    public function createGroup(User $user, string $nom, array $memberIds): DiscussionGroup
    {
        $departementId = $user->currentDepartementId();
        if (! $departementId) {
            throw ValidationException::withMessages([
                'nom' => 'Choisissez un département avant de créer un groupe.',
            ]);
        }

        $nom = $this->cleanName($nom);
        $people = $this->eligible($departementId, $memberIds, (int) $user->id);

        return DB::transaction(function () use ($user, $departementId, $nom, $people) {
            $group = DiscussionGroup::create([
                'departement_id' => $departementId,
                'created_by' => $user->id,
                'nom' => $nom,
            ]);

            $group->members()->attach($user->id, ['last_read_message_id' => null]);
            if ($people->isNotEmpty()) {
                $group->members()->attach($people->modelKeys());
            }

            $this->system($group, $user, $user->name.' a créé le groupe');
            if ($people->isNotEmpty()) {
                $this->system($group, $user, $user->name.' a ajouté '.$people->pluck('name')->implode(', '));
            }

            return $group;
        });
    }

    /**
     * @param  list<int>  $memberIds
     * @return array<string, mixed>
     */
    public function addMembers(User $user, DiscussionGroup $group, array $memberIds): array
    {
        $existing = $group->members()->pluck('users.id')->map(fn ($id) => (int) $id)->all();
        $people = $this->eligible($group->departement_id, $memberIds, null)
            ->reject(fn (User $person) => in_array((int) $person->id, $existing, true))
            ->values();

        if ($people->isEmpty()) {
            throw ValidationException::withMessages([
                'membres' => 'Sélectionnez au moins un membre à ajouter.',
            ]);
        }

        DB::transaction(function () use ($group, $user, $people) {
            $group->members()->attach($people->modelKeys());
            $this->system($group, $user, $user->name.' a ajouté '.$people->pluck('name')->implode(', '));
        });

        $group->unsetRelation('members');

        return [
            'ok' => true,
            'members' => $this->memberPayload($group, $user),
            'directory' => $this->people($group->departement_id, $group->members()->pluck('users.id')->all()),
            'messages' => $this->latestPresented($group, $user, 5),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function removeMember(User $user, DiscussionGroup $group, User $member): array
    {
        $isCreator = (int) $group->created_by === (int) $user->id;
        $removingSelf = (int) $member->id === (int) $user->id;

        if ((int) $member->id === (int) $group->created_by) {
            throw ValidationException::withMessages([
                'membre' => 'L’administrateur du groupe ne peut pas être retiré.',
            ]);
        }

        if (! $removingSelf && ! $isCreator) {
            abort(403);
        }

        if (! $group->isMember($member)) {
            abort(404);
        }

        DB::transaction(function () use ($group, $user, $member, $removingSelf) {
            $group->members()->detach($member->id);
            $this->system(
                $group,
                $user,
                $removingSelf
                    ? $user->name.' a quitté le groupe'
                    : $user->name.' a retiré '.$member->name
            );
        });

        return [
            'ok' => true,
            'left' => $removingSelf,
            'members' => $removingSelf ? [] : $this->memberPayload($group->unsetRelation('members'), $user),
            'messages' => $this->latestPresented($group, $user, 5),
        ];
    }

    /**
     * @return array{ok: bool}
     */
    public function deleteGroup(User $user, DiscussionGroup $group): array
    {
        if ((int) $group->created_by !== (int) $user->id) {
            abort(403, 'Seul l’administrateur du groupe peut le supprimer.');
        }

        $groupId = (int) $group->id;

        DB::transaction(function () use ($group) {
            $fileIds = DiscussionMessageFile::query()
                ->whereIn('discussion_message_id', $group->messages()->select('id'))
                ->pluck('id');

            DiscussionMessageFile::query()
                ->whereIn('id', $fileIds)
                ->get()
                ->each(fn (DiscussionMessageFile $file) => $file->delete());

            $group->delete();
        });

        Storage::disk('local')->deleteDirectory('discussions/'.$groupId);

        return ['ok' => true];
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    public function send(User $user, DiscussionGroup $group, ?string $body, ?int $replyToId, array $files): DiscussionMessage
    {
        $body = trim((string) $body);
        if ($body === '' && $files === []) {
            throw ValidationException::withMessages([
                'body' => 'Écrivez un message ou joignez un fichier.',
            ]);
        }

        $reply = null;
        if ($replyToId) {
            $reply = $group->messages()->whereKey($replyToId)->first();
            if (! $reply) {
                throw ValidationException::withMessages([
                    'reply_to_id' => 'Le message auquel vous répondez est introuvable.',
                ]);
            }
        }

        return DB::transaction(function () use ($user, $group, $body, $reply, $files) {
            $message = $group->messages()->create([
                'user_id' => $user->id,
                'reply_to_id' => $reply?->id,
                'body' => $body !== '' ? $body : null,
                'is_system' => false,
            ]);

            $stored = [];
            try {
                foreach ($files as $file) {
                    $path = $file->store('discussions/'.$group->id, 'local');
                    if (! $path) {
                        throw ValidationException::withMessages([
                            'fichiers' => 'Un fichier n’a pas pu être enregistré.',
                        ]);
                    }
                    $stored[] = $path;
                    $original = basename(str_replace('\\', '/', $file->getClientOriginalName()));
                    $message->files()->create([
                        'path' => $path,
                        'nom' => mb_substr($original !== '' ? $original : 'fichier', 0, 180),
                        'mime' => $file->getMimeType(),
                        'taille' => $file->getSize() ?: 0,
                    ]);
                }
            } catch (\Throwable $exception) {
                foreach ($stored as $path) {
                    PrivateFile::delete($path);
                }
                throw $exception;
            }

            $group->touch();
            $this->markRead($group, $user);

            return $message->load(['user', 'files', 'replyTo.user', 'replyTo.files']);
        });
    }

    public function unreadTotal(User $user): int
    {
        return (int) array_sum($this->unreadByGroup($user));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function summaries(User $user): array
    {
        $groups = DiscussionGroup::query()
            ->whereHas('members', fn ($query) => $query->where('users.id', $user->id))
            ->with([
                'latestMessage.user:id,name',
                'latestMessage.files',
                'members' => fn ($query) => $query->where('users.id', $user->id),
            ])
            ->withCount('members')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        $unread = $this->unreadByGroup($user);

        return $groups->map(function (DiscussionGroup $group) use ($user, $unread) {
            $message = $group->latestMessage;
            $created = $message?->created_at;

            return [
                'id' => $group->id,
                'nom' => $group->nom,
                'initials' => $this->initials($group->nom),
                'color' => $this->color((string) $group->id),
                'membres' => (int) $group->members_count,
                'apercu' => $message ? $this->preview($message, $user) : 'Aucun message',
                'heure' => $this->listTime($created),
                'unread' => (int) ($unread[$group->id] ?? 0),
                'last_id' => (int) ($message?->id ?? 0),
            ];
        })->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function thread(User $user, DiscussionGroup $group, bool $markRead): array
    {
        $messages = $group->messages()
            ->with(['user', 'files', 'replyTo.user', 'replyTo.files'])
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->sortBy('id')
            ->values();

        if ($markRead) {
            $this->markRead($group, $user);
        }

        $oldest = $messages->first();
        $memberIds = $group->members()->pluck('users.id')->all();

        return [
            'messages' => $messages->map(fn (DiscussionMessage $message) => $message->presentFor($user))->values()->all(),
            'members' => $this->memberPayload($group, $user),
            'directory' => $this->people((int) $group->departement_id, $memberIds),
            'has_more' => $oldest ? $group->messages()->where('id', '<', $oldest->id)->exists() : false,
            'latest_id' => (int) ($messages->max('id') ?? 0),
        ];
    }

    /**
     * @return array{messages: list<empty>, members: list<empty>, directory: list<empty>, has_more: bool, latest_id: int}
     */
    private function emptyThread(): array
    {
        return [
            'messages' => [],
            'members' => [],
            'directory' => [],
            'has_more' => false,
            'latest_id' => 0,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function memberPayload(DiscussionGroup $group, User $viewer): array
    {
        $group->unsetRelation('members');
        $group->load(['members' => fn ($query) => $query->orderBy('name')]);

        return $group->members->map(function (User $member) use ($group, $viewer) {
            $isCreator = (int) $member->id === (int) $group->created_by;

            return [
                'id' => $member->id,
                'name' => $member->name,
                'initials' => $member->initials(),
                'avatar_url' => $member->avatar_url,
                'color' => $member->chatColor(),
                'admin' => $isCreator,
                'self' => (int) $member->id === (int) $viewer->id,
                'removable' => ! $isCreator && (
                    (int) $viewer->id === (int) $group->created_by
                    || (int) $viewer->id === (int) $member->id
                ),
            ];
        })->values()->all();
    }

    /**
     * @param  list<int>  $exceptIds
     * @return list<array<string, mixed>>
     */
    private function people(int $departementId, array $exceptIds = []): array
    {
        $exceptIds = array_values(array_filter(array_map('intval', $exceptIds)));

        return User::query()
            ->where('departement_id', $departementId)
            ->when($exceptIds !== [], fn ($query) => $query->whereNotIn('id', $exceptIds))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'avatar_path'])
            ->map(fn (User $person) => [
                'id' => $person->id,
                'name' => $person->name,
                'initials' => $person->initials(),
                'avatar_url' => $person->avatar_url,
                'color' => $person->chatColor(),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<int>  $memberIds
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function eligible(int $departementId, array $memberIds, ?int $ignoreId)
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $memberIds), fn ($id) => $id > 0 && $id !== $ignoreId)));
        if ($ids === []) {
            return collect();
        }

        $people = User::query()
            ->where('departement_id', $departementId)
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get();

        if ($people->count() !== count($ids)) {
            throw ValidationException::withMessages([
                'membres' => 'Certains membres ne font pas partie de ce département.',
            ]);
        }

        return $people;
    }

    private function system(DiscussionGroup $group, User $actor, string $body): DiscussionMessage
    {
        $message = $group->messages()->create([
            'user_id' => $actor->id,
            'body' => $body,
            'is_system' => true,
        ]);
        $group->touch();

        return $message;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function latestPresented(DiscussionGroup $group, User $user, int $limit): array
    {
        return $group->messages()
            ->with(['user', 'files', 'replyTo.user', 'replyTo.files'])
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->sortBy('id')
            ->map(fn (DiscussionMessage $message) => $message->presentFor($user))
            ->values()
            ->all();
    }

    private function markRead(DiscussionGroup $group, User $user): void
    {
        $latest = $group->messages()->max('id');
        $group->members()->updateExistingPivot($user->id, [
            'last_read_message_id' => $latest,
        ]);
    }

    /**
     * @return array{id: int, nom: string, initials: string}
     */
    private function identity(DiscussionGroup $group): array
    {
        return [
            'id' => $group->id,
            'nom' => $group->nom,
            'initials' => $this->initials($group->nom),
            'color' => $this->color((string) $group->id),
        ];
    }

    private function preview(DiscussionMessage $message, User $viewer): string
    {
        if ($message->is_system) {
            return Str::limit(trim(preg_replace('/\s+/', ' ', (string) $message->body) ?? ''), 80);
        }

        $excerpt = $message->excerpt();
        if ((int) $message->user_id === (int) $viewer->id) {
            return 'Vous : '.$excerpt;
        }

        $first = trim(explode(' ', (string) $message->user?->name)[0] ?? '');

        return ($first !== '' ? $first.' : ' : '').$excerpt;
    }

    private function listTime($date): string
    {
        if (! $date) {
            return '';
        }

        if ($date->isToday()) {
            return $date->timezone(config('app.timezone'))->format('H:i');
        }

        if ($date->isYesterday()) {
            return 'Hier';
        }

        return $date->timezone(config('app.timezone'))->format('d/m/Y');
    }

    private function cleanName(string $nom): string
    {
        $nom = trim(preg_replace('/\s+/', ' ', $nom) ?? '');
        if ($nom === '') {
            throw ValidationException::withMessages([
                'nom' => 'Donnez un nom au groupe.',
            ]);
        }

        return mb_substr($nom, 0, 80);
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = collect($parts)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->take(2)->implode('');

        return $initials !== '' ? $initials : '?';
    }

    private function color(string $seed): string
    {
        $colors = ['#00a884', '#53bdeb', '#7f66ff', '#ff8a3d', '#e542a3', '#0063cb'];

        return $colors[abs(crc32($seed)) % count($colors)];
    }

    /**
     * @return array<int, int>
     */
    private function unreadByGroup(User $user): array
    {
        return DiscussionMessage::query()
            ->join('discussion_group_user as membership', function ($join) use ($user) {
                $join->on('membership.discussion_group_id', '=', 'discussion_messages.discussion_group_id')
                    ->where('membership.user_id', '=', $user->id);
            })
            ->where(function ($query) use ($user) {
                $query->whereNull('discussion_messages.user_id')
                    ->orWhere('discussion_messages.user_id', '!=', $user->id);
            })
            ->where(function ($query) {
                $query->whereNull('membership.last_read_message_id')
                    ->orWhereColumn('discussion_messages.id', '>', 'membership.last_read_message_id');
            })
            ->groupBy('discussion_messages.discussion_group_id')
            ->selectRaw('discussion_messages.discussion_group_id as group_id, COUNT(*) as aggregate')
            ->pluck('aggregate', 'group_id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }
}
