<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\ItTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ItTicketDepartementScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');
        config(['app.url' => 'http://localhost']);
    }

    public function test_department_admin_cannot_read_another_departments_it_tickets(): void
    {
        $communication = Departement::query()->where('slug', 'communication')->firstOrFail();
        $commercial = Departement::query()->where('slug', 'commercial')->firstOrFail();

        $commAdmin = User::factory()->create([
            'role' => 'administrateur',
            'departement_id' => $communication->id,
            'password' => 'password',
        ]);
        $salesUser = User::factory()->create([
            'role' => 'stagiaire',
            'departement_id' => $commercial->id,
            'password' => 'password',
        ]);
        $commUser = User::factory()->create([
            'role' => 'stagiaire',
            'departement_id' => $communication->id,
            'password' => 'password',
        ]);

        $leaked = ItTicket::create([
            'user_id' => $salesUser->id,
            'departement_id' => $commercial->id,
            'titre' => 'Incident VPN commercial',
            'description' => 'Secret commercial qui ne doit pas fuiter.',
            'resultat_attendu' => 'Accès rétabli',
            'impact' => 'haut',
            'statut' => 'en_attente',
        ]);
        $local = ItTicket::create([
            'user_id' => $commUser->id,
            'departement_id' => $communication->id,
            'titre' => 'Imprimante communication',
            'description' => 'Le bac papier est bloqué.',
            'resultat_attendu' => 'Impression possible',
            'impact' => 'passable',
            'statut' => 'en_attente',
        ]);

        $this->actingAs($commAdmin)
            ->get(route('it-tickets.index'))
            ->assertOk()
            ->assertSee('Imprimante communication')
            ->assertDontSee('Incident VPN commercial')
            ->assertDontSee('Secret commercial qui ne doit pas fuiter.');

        $this->actingAs($commAdmin)
            ->get(route('it-tickets.show', $leaked))
            ->assertNotFound();

        $this->actingAs($commAdmin)
            ->get(route('it-tickets.show', $local))
            ->assertOk()
            ->assertSee('Le bac papier est bloqué.');

        $this->actingAs($commAdmin)
            ->patch(route('it-tickets.status', $leaked), [
                'statut' => 'annule',
            ])
            ->assertNotFound();

        $this->assertSame(
            'en_attente',
            ItTicket::withoutGlobalScopes()->findOrFail($leaked->id)->statut
        );
    }

    public function test_super_admin_scoped_to_a_department_does_not_see_other_tickets(): void
    {
        $communication = Departement::query()->where('slug', 'communication')->firstOrFail();
        $commercial = Departement::query()->where('slug', 'commercial')->firstOrFail();

        $admin = User::factory()->create([
            'role' => 'super_admin',
            'departement_id' => $communication->id,
            'password' => 'password',
        ]);
        $salesUser = User::factory()->create([
            'role' => 'stagiaire',
            'departement_id' => $commercial->id,
            'password' => 'password',
        ]);

        ItTicket::create([
            'user_id' => $salesUser->id,
            'departement_id' => $commercial->id,
            'titre' => 'Ticket commercial isolé',
            'description' => 'Visible seulement côté commercial.',
            'statut' => 'en_attente',
        ]);

        $this->actingAs($admin)
            ->get(route('it-tickets.index'))
            ->assertOk()
            ->assertDontSee('Ticket commercial isolé');

        $this->actingAs($admin)
            ->withSession(['departement_actif_id' => $commercial->id])
            ->get(route('it-tickets.index'))
            ->assertOk()
            ->assertSee('Ticket commercial isolé');
    }
}
