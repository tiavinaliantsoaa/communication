<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');
        config(['app.url' => 'http://localhost']);
    }

    public function test_a_logged_in_user_sees_the_sidebar_on_a_missing_page(): void
    {
        $communication = Departement::query()->where('slug', 'communication')->firstOrFail();
        $user = User::factory()->create([
            'role' => 'super_admin',
            'departement_id' => $communication->id,
            'password' => 'password',
        ]);

        $this->actingAs($user)
            ->get('/page-qui-n-existe-pas')
            ->assertNotFound()
            ->assertSee('Page introuvable')
            ->assertSee('Dashboard')
            ->assertSee('La page que vous recherchez n’existe pas ou a été déplacée.');
    }

    public function test_a_guest_sees_a_standalone_not_found_page(): void
    {
        $this->get('/page-qui-n-existe-pas')
            ->assertNotFound()
            ->assertSee('Page introuvable')
            ->assertSee('Se connecter')
            ->assertDontSee('CV enseignant');
    }

    public function test_forbidden_page_keeps_the_sidebar(): void
    {
        $communication = Departement::query()->where('slug', 'communication')->firstOrFail();
        $user = User::factory()->create([
            'role' => 'sans_acces',
            'departement_id' => $communication->id,
            'password' => 'password',
        ]);

        $this->actingAs($user)
            ->get(route('navbar.index'))
            ->assertForbidden()
            ->assertSee('Accès refusé')
            ->assertSee('Paramètres');
    }
}
