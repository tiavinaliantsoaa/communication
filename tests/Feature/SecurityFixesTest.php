<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\Permission;
use App\Models\ProjetCarte;
use App\Models\ProjetListe;
use App\Models\ProjetTableau;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SecurityFixesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');
        config(['app.url' => 'http://localhost']);
        Permission::syncCatalog();
    }

    public function test_api_token_without_permission_cannot_read_the_board(): void
    {
        $communication = Departement::query()->where('slug', 'communication')->firstOrFail();
        $user = User::factory()->create([
            'role' => 'sans_acces',
            'departement_id' => $communication->id,
            'password' => 'password',
        ]);

        $this->withToken($user->createToken('test')->plainTextToken)
            ->getJson('/api/projet/board')
            ->assertForbidden();
    }

    public function test_api_refuses_a_list_from_another_department(): void
    {
        $communication = Departement::query()->where('slug', 'communication')->firstOrFail();
        $commercial = Departement::query()->where('slug', 'commercial')->firstOrFail();
        $commUser = User::factory()->create([
            'role' => 'sans_acces',
            'departement_id' => $communication->id,
            'password' => 'password',
        ]);
        $salesUser = User::factory()->create([
            'role' => 'sans_acces',
            'departement_id' => $commercial->id,
            'password' => 'password',
        ]);
        $salesUser->permissions()->sync(
            Permission::query()->where('key', 'gestion_projet.create')->pluck('id')
        );

        $this->actingAs($commUser);
        ProjetTableau::current();
        $liste = ProjetListe::query()->where('slug', 'termine')->firstOrFail();

        auth()->logout();
        $this->withToken($salesUser->createToken('test')->plainTextToken)
            ->postJson('/api/projet/cartes', [
                'titre' => 'Hors département',
                'projet_liste_id' => $liste->id,
            ])
            ->assertNotFound();
    }

    public function test_view_only_user_cannot_create_a_depense(): void
    {
        $communication = Departement::query()->where('slug', 'communication')->firstOrFail();
        $user = User::factory()->create([
            'role' => 'sans_acces',
            'departement_id' => $communication->id,
            'password' => 'password',
        ]);
        $user->permissions()->sync(
            Permission::query()->where('key', 'depenses.view')->pluck('id')
        );

        $this->actingAs($user)
            ->post(route('depenses.store'), [])
            ->assertForbidden();
    }

    public function test_html_attachment_is_rejected_and_pdf_is_not_public(): void
    {
        $communication = Departement::query()->where('slug', 'communication')->firstOrFail();
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'departement_id' => $communication->id,
            'password' => 'password',
        ]);

        $this->actingAs($admin);
        ProjetTableau::current();
        $liste = ProjetListe::query()->where('slug', 'termine')->firstOrFail();
        $carte = ProjetCarte::create([
            'titre' => 'Dossier',
            'projet_liste_id' => $liste->id,
            'position' => 1,
            'created_by' => $admin->id,
        ]);

        $this->postJson(route('gestion-projet.cartes.pieces', $carte), [
            'fichier' => UploadedFile::fake()->create('evil.html', 20, 'text/html'),
        ])->assertStatus(422);

        $response = $this->postJson(route('gestion-projet.cartes.pieces', $carte), [
            'fichier' => UploadedFile::fake()->create('note.pdf', 20, 'application/pdf'),
        ]);
        $response->assertOk();
        $this->assertStringNotContainsString('/storage/', (string) $response->json('piece.url'));

        auth()->logout();
        $this->get(route('gestion-projet.pieces.download', $response->json('piece.id')))
            ->assertRedirect(route('login'));
    }

    public function test_comment_formatter_escapes_attribute_breakout(): void
    {
        $source = file_get_contents(resource_path('views/projets/index.blade.php'));

        $this->assertStringContainsString(".replace(/\"/g, '&quot;')", $source);
        $this->assertStringContainsString('if (!/^https?:\\/\\//i.test(href)) return raw;', $source);
    }

    public function test_administrator_cannot_become_super_admin_or_edit_another_department(): void
    {
        $communication = Departement::query()->where('slug', 'communication')->firstOrFail();
        $commercial = Departement::query()->where('slug', 'commercial')->firstOrFail();
        $admin = User::factory()->create([
            'role' => 'administrateur',
            'departement_id' => $communication->id,
            'password' => 'password',
        ]);
        $admin->permissions()->sync(
            Permission::query()->whereIn('key', ['users.view', 'users.update'])->pluck('id')
        );
        $colleague = User::factory()->create([
            'role' => 'stagiaire',
            'departement_id' => $commercial->id,
            'password' => 'password',
        ]);

        $this->actingAs($admin)
            ->put(route('users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'super_admin',
                'departement_id' => $communication->id,
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->put(route('users.update', $colleague), [
                'name' => $colleague->name,
                'email' => $colleague->email,
                'role' => 'stagiaire',
                'departement_id' => $commercial->id,
            ])
            ->assertForbidden();
    }
}
