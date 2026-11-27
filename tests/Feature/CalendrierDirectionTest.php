<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\EditorialEvent;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CalendrierDirectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');
        config(['app.url' => 'http://localhost']);
        Permission::syncCatalog();
    }

    public function test_direction_sees_every_departments_editorial_calendar(): void
    {
        $communication = Departement::query()->where('slug', 'communication')->firstOrFail();
        $commercial = Departement::query()->where('slug', 'commercial')->firstOrFail();
        $pedagogique = Departement::query()->where('slug', 'pedagogique')->firstOrFail();
        $direction = Departement::query()->create([
            'nom' => 'Direction',
            'slug' => 'direction',
            'is_system' => false,
        ]);

        $communicationUser = $this->user($communication->id, 'responsable_communication', ['calendrier_saan.view']);
        $commercialUser = $this->user($commercial->id, 'stagiaire', ['calendrier_saan.view']);
        $pedagogiqueUser = $this->user($pedagogique->id, 'stagiaire', ['calendrier_saan.view']);
        $directionUser = $this->user($direction->id, 'direction', ['calendrier_saan.view', 'calendrier_saan.update', 'calendrier_saan.create']);
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'departement_id' => $communication->id,
            'password' => 'password',
        ]);

        $this->event($communicationUser, 'Publication SAAN communication');
        $this->event($commercialUser, 'Publication SAAN commerciale');

        $this->actingAs($communicationUser)
            ->get(route('calendrier-saan'))
            ->assertOk()
            ->assertSee('Publication SAAN communication')
            ->assertDontSee('Publication SAAN commerciale');

        $this->actingAs($pedagogiqueUser)
            ->get(route('calendrier-saan'))
            ->assertOk()
            ->assertDontSee('Publication SAAN communication')
            ->assertDontSee('Publication SAAN commerciale');

        $this->actingAs($directionUser)
            ->get(route('calendrier-saan'))
            ->assertOk()
            ->assertSee('Publication SAAN communication')
            ->assertSee('Publication SAAN commerciale');

        $this->actingAs($admin)
            ->withSession(['departement_actif_id' => $direction->id])
            ->get(route('calendrier-saan'))
            ->assertOk()
            ->assertSee('Publication SAAN communication')
            ->assertSee('Publication SAAN commerciale');

        $event = EditorialEvent::withoutGlobalScope('departement')
            ->where('titre', 'Publication SAAN communication')
            ->firstOrFail();

        $this->actingAs($directionUser)
            ->put(route('calendrier-saan.update', $event), [
                'titre' => 'Publication SAAN corrigee',
                'categorie' => ['facebook'],
                'type_contenu' => 'FP',
                'date_debut' => now()->toDateString(),
                'statut' => 'planifie',
                'texte_publication' => 'Texte',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $event->refresh();
        $this->assertSame('Publication SAAN corrigee', $event->titre);
        $this->assertSame($communication->id, (int) $event->departement_id);

        $this->actingAs($admin)
            ->withSession(['departement_actif_id' => $direction->id])
            ->post(route('calendrier-saan.store'), [
                'titre' => 'Publication ajoutee par la direction',
                'categorie' => ['instagram'],
                'type_contenu' => 'FI',
                'date_debut' => now()->toDateString(),
                'statut' => 'planifie',
                'texte_publication' => 'Texte',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $created = EditorialEvent::withoutGlobalScope('departement')
            ->where('titre', 'Publication ajoutee par la direction')
            ->firstOrFail();
        $this->assertSame($communication->id, (int) $created->departement_id);

        $this->actingAs($communicationUser)
            ->get(route('calendrier-saan'))
            ->assertOk()
            ->assertSee('Publication ajoutee par la direction')
            ->assertDontSee('Publication SAAN commerciale');
    }

    /**
     * @param  list<string>  $keys
     */
    private function user(int $departementId, string $role, array $keys): User
    {
        $user = User::factory()->create([
            'role' => $role,
            'departement_id' => $departementId,
            'password' => 'password',
        ]);
        $user->permissions()->sync(
            Permission::query()->whereIn('key', $keys)->pluck('id')
        );

        return $user;
    }

    private function event(User $owner, string $titre): EditorialEvent
    {
        $this->actingAs($owner);

        return EditorialEvent::create([
            'service' => EditorialEvent::SERVICE_SAAN,
            'titre' => $titre,
            'categorie' => ['facebook'],
            'type_contenu' => 'FP',
            'date_debut' => now()->toDateString(),
            'statut' => 'planifie',
            'valide' => false,
            'texte_publication' => 'Texte',
        ]);
    }
}
