<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class DiscussionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');
        config(['app.url' => 'http://localhost']);
        Permission::syncCatalog();
    }

    public function test_discussion_replaces_statistiques_in_the_sidebar(): void
    {
        $user = $this->member('communication');

        $this->actingAs($user)
            ->get(route('discussions.index'))
            ->assertOk()
            ->assertSee('Discussion')
            ->assertSee('Nouveau groupe')
            ->assertDontSee('/statistiques', false);
    }

    public function test_only_members_can_read_and_poll_a_group(): void
    {
        $author = $this->member('communication');
        $guest = $this->member('communication', 'Invite');
        $otherDepartment = $this->member('commercial', 'Commercial');

        $this->actingAs($author)
            ->postJson(route('discussions.store'), [
                'nom' => 'Équipe com',
                'membres' => [$otherDepartment->id],
            ])
            ->assertStatus(422);

        $create = $this->actingAs($author)
            ->postJson(route('discussions.store'), [
                'nom' => 'Équipe com',
                'membres' => [$guest->id],
            ])
            ->assertOk();

        $groupUrl = $create->json('redirect');
        $groupId = (int) substr($groupUrl, strrpos($groupUrl, '=') + 1);

        $this->actingAs($guest)
            ->getJson(route('discussions.messages', $groupId))
            ->assertOk()
            ->assertJsonPath('group.nom', 'Équipe com');

        $outsider = $this->member('communication', 'Exclu');
        $this->actingAs($outsider)
            ->getJson(route('discussions.messages', $groupId))
            ->assertNotFound();

        $this->actingAs($outsider)
            ->getJson(route('discussions.poll', ['groupe' => $groupId]))
            ->assertNotFound();

        $admin = User::factory()->create([
            'role' => 'super_admin',
            'departement_id' => Departement::query()->where('slug', 'communication')->value('id'),
            'password' => 'password',
        ]);
        $this->actingAs($admin)
            ->getJson(route('discussions.messages', $groupId))
            ->assertNotFound();
    }

    public function test_members_can_reply_and_attach_a_file(): void
    {
        Storage::fake('local');
        $author = $this->member('communication');
        $guest = $this->member('communication', 'Invite');

        $groupId = $this->groupId($author, $guest);

        $first = $this->actingAs($author)
            ->postJson(route('discussions.messages.store', $groupId), [
                'body' => 'On valide le visuel ?',
            ])
            ->assertOk()
            ->json('message');

        $reply = $this->actingAs($guest)
            ->postJson(route('discussions.messages.store', $groupId), [
                'body' => 'Oui, je l’envoie.',
                'reply_to_id' => $first['id'],
                'fichiers' => [UploadedFile::fake()->image('visuel.jpg')],
            ])
            ->assertOk();

        $reply->assertJsonPath('message.reply.id', $first['id']);
        $reply->assertJsonPath('message.reply.excerpt', 'On valide le visuel ?');
        $fileId = $reply->json('message.files.0.id');
        $this->assertNotNull($fileId);

        $this->actingAs($guest)
            ->get(route('discussions.files.show', $fileId))
            ->assertOk();

        $outsider = $this->member('communication', 'Exclu');
        $this->actingAs($outsider)
            ->get(route('discussions.files.show', $fileId))
            ->assertNotFound();

        $this->actingAs($author)
            ->postJson(route('discussions.messages.store', $groupId), [
                'fichiers' => [UploadedFile::fake()->create('evil.html', 10, 'text/html')],
            ])
            ->assertStatus(422);

        $this->actingAs($author)
            ->getJson(route('discussions.poll', ['groupe' => $groupId, 'since' => $first['id']]))
            ->assertOk()
            ->assertJsonPath('messages.0.body', 'Oui, je l’envoie.');
    }

    public function test_a_member_added_later_can_read_the_group(): void
    {
        $author = $this->member('communication');
        $later = $this->member('communication', 'Nouveau');
        $groupId = $this->groupId($author);

        $this->actingAs($later)
            ->getJson(route('discussions.messages', $groupId))
            ->assertNotFound();

        $this->actingAs($author)
            ->postJson(route('discussions.members.store', $groupId), [
                'membres' => [$later->id],
            ])
            ->assertOk();

        $this->actingAs($later)
            ->getJson(route('discussions.messages', $groupId))
            ->assertOk()
            ->assertJsonFragment(['body' => $author->name.' a ajouté '.$later->name]);
    }

    public function test_only_the_group_admin_can_delete_the_group_and_its_files(): void
    {
        Storage::fake('local');
        $author = $this->member('communication');
        $guest = $this->member('communication', 'Invite');
        $groupId = $this->groupId($author, $guest);

        $this->actingAs($author)
            ->postJson(route('discussions.messages.store', $groupId), [
                'body' => 'Avec fichier',
                'fichiers' => [UploadedFile::fake()->create('note.pdf', 20, 'application/pdf')],
            ])
            ->assertOk();

        $this->assertTrue(
            collect(Storage::disk('local')->allFiles('discussions/'.$groupId))->isNotEmpty()
        );

        $this->actingAs($guest)
            ->deleteJson(route('discussions.destroy', $groupId))
            ->assertForbidden();

        $this->actingAs($author)
            ->deleteJson(route('discussions.destroy', $groupId))
            ->assertOk();

        $this->assertDatabaseMissing('discussion_groups', ['id' => $groupId]);
        $this->assertDatabaseMissing('discussion_messages', ['discussion_group_id' => $groupId]);
        $this->assertSame([], Storage::disk('local')->allFiles('discussions/'.$groupId));

        $this->actingAs($author)
            ->getJson(route('discussions.messages', $groupId))
            ->assertNotFound();
    }

    private function member(string $slug, string $name = 'Auteur'): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'role' => 'stagiaire',
            'departement_id' => Departement::query()->where('slug', $slug)->value('id'),
            'password' => 'password',
        ]);
        $user->permissions()->sync(
            Permission::query()->where('key', 'discussions.view')->pluck('id')
        );

        return $user;
    }

    private function groupId(User $author, ?User $guest = null): int
    {
        $payload = ['nom' => 'Groupe test'];
        if ($guest) {
            $payload['membres'] = [$guest->id];
        }

        $redirect = $this->actingAs($author)
            ->postJson(route('discussions.store'), $payload)
            ->assertOk()
            ->json('redirect');

        return (int) substr($redirect, strrpos($redirect, '=') + 1);
    }
}
