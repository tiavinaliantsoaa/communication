<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\Depense;
use App\Models\TrackedLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class DepartementScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');
        config(['app.url' => 'http://localhost']);
    }

    public function test_each_department_only_sees_its_own_records(): void
    {
        $communication = Departement::query()->where('slug', 'communication')->firstOrFail();
        $commercial = Departement::query()->where('slug', 'commercial')->firstOrFail();

        $commUser = User::factory()->create([
            'role' => 'responsable_communication',
            'departement_id' => $communication->id,
            'password' => 'password',
        ]);
        $salesUser = User::factory()->create([
            'role' => 'responsable_communication',
            'departement_id' => $commercial->id,
            'password' => 'password',
        ]);

        $this->actingAs($commUser);
        Depense::create([
            'fournisseur' => 'Studio',
            'objet' => 'Flyer communication',
            'montant' => 1000,
            'date_depense' => '2026-09-01',
        ]);

        $this->actingAs($salesUser);
        $this->assertSame(0, Depense::query()->count());

        Depense::create([
            'fournisseur' => 'Salon',
            'objet' => 'Stand commercial',
            'montant' => 2000,
            'date_depense' => '2026-09-02',
        ]);

        $this->assertSame(['Stand commercial'], Depense::query()->pluck('objet')->all());

        $this->actingAs($commUser);
        $this->assertSame(['Flyer communication'], Depense::query()->pluck('objet')->all());
    }

    public function test_super_admin_switches_department_and_assigns_users(): void
    {
        $communication = Departement::query()->where('slug', 'communication')->firstOrFail();
        $commercial = Departement::query()->where('slug', 'commercial')->firstOrFail();
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'departement_id' => $communication->id,
            'password' => 'password',
        ]);

        $this->actingAs($admin);
        Depense::create([
            'fournisseur' => 'Studio',
            'objet' => 'Flyer communication',
            'montant' => 1000,
            'date_depense' => '2026-09-01',
        ]);

        $this->post(route('departement.switch'), [
            'departement_id' => $commercial->id,
        ])->assertRedirect();

        $this->assertSame(0, Depense::query()->count());

        $this->get(route('users.index'))
            ->assertOk()
            ->assertSee('Départements')
            ->assertSee('Communication')
            ->assertSee('Commercial')
            ->assertSee('Pédagogique')
            ->assertSee('Nouvel utilisateur');

        $this->post(route('departements.store'), [
            'nom' => 'Alumni',
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('departements', ['nom' => 'Alumni', 'is_system' => false]);

        $this->post(route('users.store'), [
            'name' => 'Aina R.',
            'email' => 'aina@escm.mg',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'stagiaire',
            'departement_id' => $commercial->id,
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'aina@escm.mg',
            'departement_id' => $commercial->id,
        ]);

        $this->delete(route('departements.destroy', $communication))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_a_tracked_link_stays_public_for_other_departments(): void
    {
        $communication = Departement::query()->where('slug', 'communication')->firstOrFail();
        $commercial = Departement::query()->where('slug', 'commercial')->firstOrFail();
        $commUser = User::factory()->create([
            'role' => 'responsable_communication',
            'departement_id' => $communication->id,
            'password' => 'password',
        ]);
        $salesUser = User::factory()->create([
            'role' => 'responsable_communication',
            'departement_id' => $commercial->id,
            'password' => 'password',
        ]);

        $this->actingAs($commUser);
        $link = TrackedLink::create([
            'user_id' => $commUser->id,
            'nom' => 'Inscription',
            'destination_url' => 'https://www.escm.mg/inscription',
            'slug' => 'inscription-comm',
            'actif' => true,
        ]);

        $this->actingAs($salesUser);
        $this->assertNull(TrackedLink::query()->find($link->id));

        $this->get(route('liens.redirect', $link->slug))
            ->assertRedirect('https://www.escm.mg/inscription');
    }
}
