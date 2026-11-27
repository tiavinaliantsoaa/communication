<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class DirectionRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');
        config(['app.url' => 'http://localhost']);
    }

    public function test_direction_is_a_default_role_with_read_or_write_access_controls(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'password' => 'password',
        ]);
        $direction = Role::query()->where('slug', 'direction')->firstOrFail();
        $this->assertTrue($direction->is_system);
        $this->assertTrue($direction->permissions()->where('key', 'calendrier_editorial.view')->exists());
        $this->assertFalse($direction->permissions()->where('key', 'calendrier_editorial.create')->exists());

        $this->actingAs($admin)
            ->get(route('acces.index'))
            ->assertOk()
            ->assertSee('Direction');

        $this->actingAs($admin)
            ->get(route('users.create'))
            ->assertOk()
            ->assertSee('Direction')
            ->assertSee('Étudiant')
            ->assertSee('Parent')
            ->assertSee('Enseignant');

        $this->actingAs($admin)
            ->get(route('acces.roles.edit', $direction))
            ->assertOk()
            ->assertSee('Calendrier ESCM')
            ->assertSee('Lecture seule')
            ->assertSee('Lecture et écriture');

        $this->actingAs($admin)
            ->put(route('acces.roles.update', $direction), [
                'name' => 'Direction',
                'description' => $direction->description,
                'home_route' => 'dashboard',
                'permissions' => [
                    'calendrier_editorial.view',
                    'calendrier_editorial.create',
                    'calendrier_editorial.update',
                    'calendrier_editorial.delete',
                    'calendrier_editorial.validate',
                ],
            ])
            ->assertRedirect();

        $this->assertTrue($direction->permissions()->where('key', 'calendrier_editorial.update')->exists());
    }
}
