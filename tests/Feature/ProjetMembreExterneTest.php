<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\ProjetCarte;
use App\Models\ProjetListe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ProjetMembreExterneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');
        config(['app.url' => 'http://localhost']);
    }

    public function test_an_external_member_sees_the_project_on_their_department_board(): void
    {
        $communication = Departement::query()->where('slug', 'communication')->firstOrFail();
        $pedagogique = Departement::query()->where('slug', 'pedagogique')->firstOrFail();
        $commercial = Departement::query()->where('slug', 'commercial')->firstOrFail();
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'departement_id' => $communication->id,
            'password' => 'password',
        ]);
        $invite = User::factory()->create([
            'name' => 'Mialy Rasoanaivo',
            'role' => 'responsable_communication',
            'departement_id' => $pedagogique->id,
            'password' => 'password',
        ]);

        $this->actingAs($admin)->withSession(['departement_actif_id' => $communication->id]);
        $this->post(route('gestion-projet.listes.store'), ['nom' => 'À faire'])->assertRedirect();
        $liste = ProjetListe::query()->where('nom', 'À faire')->firstOrFail();
        $this->post(route('gestion-projet.cartes.store'), [
            'titre' => 'Salon ESCM',
            'projet_liste_id' => $liste->id,
        ])->assertRedirect();

        $carte = ProjetCarte::query()->where('titre', 'Salon ESCM')->firstOrFail();
        $checklist = $carte->checklists()->create(['titre' => 'Préparer', 'position' => 0]);
        $checklist->items()->create(['titre' => 'Flyer', 'fait' => false, 'position' => 0]);

        $this->postJson(route('gestion-projet.cartes.membres', $carte), [
            'user_ids' => [$invite->id],
        ])->assertOk();

        $this->get(route('gestion-projet.index'))
            ->assertOk()
            ->assertSee('Membre externe')
            ->assertSee('Mialy Rasoanaivo')
            ->assertSee('Pédagogique')
            ->assertSee('Salon ESCM');

        $this->withSession(['departement_actif_id' => $pedagogique->id])
            ->get(route('gestion-projet.index'))
            ->assertOk()
            ->assertSee('Salon ESCM')
            ->assertSee('Communication');

        $this->withSession(['departement_actif_id' => $pedagogique->id])
            ->getJson(route('gestion-projet.cartes.show', $carte))
            ->assertOk()
            ->assertJsonPath('titre', 'Salon ESCM')
            ->assertJsonPath('externe', true)
            ->assertJsonFragment(['titre' => 'Flyer']);

        $this->withSession(['departement_actif_id' => $commercial->id])
            ->get(route('gestion-projet.index'))
            ->assertOk()
            ->assertDontSee('Salon ESCM');

        $this->withSession(['departement_actif_id' => $commercial->id])
            ->getJson(route('gestion-projet.cartes.show', $carte))
            ->assertNotFound();
    }
}
