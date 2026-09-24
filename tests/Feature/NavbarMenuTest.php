<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\User;
use App\Support\NavbarMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class NavbarMenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');
        config(['app.url' => 'http://localhost']);
    }

    public function test_super_admin_can_hide_a_section_for_one_department(): void
    {
        $commercial = Departement::query()->where('slug', 'commercial')->firstOrFail();
        $communication = Departement::query()->where('slug', 'communication')->firstOrFail();
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'departement_id' => $communication->id,
            'password' => 'password',
        ]);

        $this->actingAs($admin)
            ->get(route('navbar.index'))
            ->assertOk()
            ->assertSee('Gestion liste navbar')
            ->assertSee('Commercial')
            ->assertSee('CRM');

        $menus = [];
        foreach (Departement::query()->pluck('id') as $id) {
            $keys = NavbarMenu::keys();
            if ((int) $id === (int) $commercial->id) {
                $keys = array_values(array_diff($keys, ['crm']));
            }
            $menus[$id] = $keys;
        }

        $this->actingAs($admin)
            ->put(route('navbar.update'), [
                'departements' => Departement::query()->pluck('id')->all(),
                'menus' => $menus,
            ])
            ->assertRedirect(route('navbar.index'));

        $this->assertFalse(NavbarMenu::enabled($commercial->id, 'crm'));
        $this->assertTrue(NavbarMenu::enabled($communication->id, 'crm'));

        $this->actingAs($admin)
            ->withSession(['departement_actif_id' => $commercial->id])
            ->get(route('crm.dashboard'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->withSession(['departement_actif_id' => $commercial->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Pipeline');

        $this->actingAs($admin)
            ->withSession(['departement_actif_id' => $communication->id])
            ->get(route('crm.dashboard'))
            ->assertOk();
    }

    public function test_non_super_admin_cannot_manage_navbar(): void
    {
        $communication = Departement::query()->where('slug', 'communication')->firstOrFail();
        $user = User::factory()->create([
            'role' => 'responsable_communication',
            'departement_id' => $communication->id,
            'password' => 'password',
        ]);

        $this->actingAs($user)
            ->get(route('navbar.index'))
            ->assertForbidden();
    }
}
