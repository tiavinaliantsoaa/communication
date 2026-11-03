<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\BudgetAnnuel;
use App\Models\Departement;
use App\Models\DepenseCategorie;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class BudgetLigneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');
        config(['app.url' => 'http://localhost']);
        Permission::syncCatalog();
    }

    public function test_monthly_budget_can_be_split_by_categories(): void
    {
        $communication = Departement::query()->where('slug', 'communication')->firstOrFail();
        $user = User::factory()->create([
            'role' => 'super_admin',
            'departement_id' => $communication->id,
            'password' => 'password',
        ]);

        BudgetAnnuel::create(['annee' => 2026, 'montant' => 5000000]);

        $this->actingAs($user);
        $catA = DepenseCategorie::query()->where('slug', 'communication')->first()
            ?? DepenseCategorie::create([
                'nom' => 'Communication',
                'slug' => 'communication_test',
                'position' => 1,
            ]);
        $catB = DepenseCategorie::query()->where('slug', 'transport')->first()
            ?? DepenseCategorie::create([
                'nom' => 'Transport',
                'slug' => 'transport_test',
                'position' => 2,
            ]);

        $this->actingAs($user)
            ->post(route('budgets.store'), [
                'montant' => 1500000,
                'annee' => 2026,
                'mois' => 11,
                'lignes' => [
                    ['depense_categorie_id' => $catA->id, 'montant' => 900000],
                    ['depense_categorie_id' => $catB->id, 'montant' => 600000],
                ],
            ])
            ->assertRedirect(route('budgets.index'))
            ->assertSessionHasNoErrors();

        $budget = Budget::query()->where('annee', 2026)->where('mois', 11)->first();
        $this->assertNotNull($budget);
        $this->assertCount(2, $budget->lignes);
        $this->assertEquals(1500000.0, (float) $budget->lignes->sum('montant'));
    }

    public function test_category_allocation_cannot_exceed_monthly_budget(): void
    {
        $communication = Departement::query()->where('slug', 'communication')->firstOrFail();
        $user = User::factory()->create([
            'role' => 'super_admin',
            'departement_id' => $communication->id,
            'password' => 'password',
        ]);

        $this->actingAs($user);
        $cat = DepenseCategorie::query()->first()
            ?? DepenseCategorie::create(['nom' => 'Divers', 'slug' => 'divers_test', 'position' => 1]);

        $this->actingAs($user)
            ->from(route('budgets.create'))
            ->post(route('budgets.store'), [
                'montant' => 100000,
                'annee' => 2026,
                'mois' => 3,
                'lignes' => [
                    ['depense_categorie_id' => $cat->id, 'montant' => 150000],
                ],
            ])
            ->assertRedirect(route('budgets.create'))
            ->assertSessionHasErrors('lignes');
    }
}
