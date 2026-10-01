<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\Depense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PeriodeFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');
        config(['app.url' => 'http://localhost']);
    }

    public function test_the_chosen_month_stays_when_changing_page(): void
    {
        $communication = Departement::query()->where('slug', 'communication')->firstOrFail();
        $user = User::factory()->create([
            'role' => 'super_admin',
            'departement_id' => $communication->id,
            'password' => 'password',
        ]);

        $this->actingAs($user);
        Depense::create([
            'fournisseur' => 'Studio',
            'objet' => 'Dépense février',
            'montant' => 1000,
            'date_depense' => '2026-02-10',
            'statut' => 'en_attente',
        ]);
        Depense::create([
            'fournisseur' => 'Studio',
            'objet' => 'Dépense mars',
            'montant' => 2000,
            'date_depense' => '2026-03-10',
            'statut' => 'en_attente',
        ]);

        $this->get(route('depenses.index', ['annee' => 2026, 'mois' => 2]))
            ->assertOk()
            ->assertSee('Dépense février')
            ->assertDontSee('Dépense mars')
            ->assertSee('Février 2026');

        $this->get(route('depenses.index'))
            ->assertOk()
            ->assertSee('Dépense février')
            ->assertDontSee('Dépense mars')
            ->assertSee('Février 2026');
    }
}
