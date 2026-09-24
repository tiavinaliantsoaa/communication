<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\ProjetListe;
use App\Models\ProjetTableau;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ProjetTermineListeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');
        config(['app.url' => 'http://localhost']);
    }

    public function test_each_department_gets_a_termine_list_that_cannot_be_deleted(): void
    {
        $communication = Departement::query()->where('slug', 'communication')->firstOrFail();
        $commercial = Departement::query()->where('slug', 'commercial')->firstOrFail();
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'departement_id' => $communication->id,
            'password' => 'password',
        ]);

        $this->actingAs($admin)
            ->withSession(['departement_actif_id' => $commercial->id])
            ->get(route('gestion-projet.index'))
            ->assertOk()
            ->assertSee('Terminé');

        $liste = ProjetListe::query()->where('slug', 'termine')->firstOrFail();
        $this->assertSame(
            $commercial->id,
            (int) ProjetTableau::query()->findOrFail($liste->projet_tableau_id)->departement_id
        );

        $this->actingAs($admin)
            ->withSession(['departement_actif_id' => $commercial->id])
            ->delete(route('gestion-projet.listes.destroy', $liste))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNotNull(ProjetListe::query()->find($liste->id));
    }
}
