<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\Enseignant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EnseignantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');
        config(['app.url' => 'http://localhost']);
    }

    public function test_teachers_are_listed_as_cards_and_open_a_profile(): void
    {
        $communication = Departement::query()->where('slug', 'communication')->firstOrFail();
        $user = User::factory()->create([
            'role' => 'super_admin',
            'departement_id' => $communication->id,
            'password' => 'password',
        ]);

        $this->actingAs($user)
            ->post(route('enseignants.store'), [
                'prenom' => 'Aina',
                'nom' => 'Rasoanaivo',
                'fonction' => 'Professeur de marketing',
                'biographie' => 'Dix ans d’enseignement.',
            ])
            ->assertRedirect();

        $enseignant = Enseignant::query()->where('nom', 'Rasoanaivo')->firstOrFail();

        $this->actingAs($user)
            ->get(route('enseignants.index'))
            ->assertOk()
            ->assertSee('Aina Rasoanaivo')
            ->assertSee(route('enseignants.show', $enseignant), false);

        $this->actingAs($user)
            ->get(route('enseignants.show', $enseignant))
            ->assertOk()
            ->assertSee('Professeur de marketing')
            ->assertSee('Dix ans d’enseignement.');
    }
}
