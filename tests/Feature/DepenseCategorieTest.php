<?php

namespace Tests\Feature;

use App\Models\BudgetAnnuel;
use App\Models\Departement;
use App\Models\DepenseCategorie;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class DepenseCategorieTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');
        config(['app.url' => 'http://localhost']);
        Permission::syncCatalog();
    }

    public function test_a_department_only_sees_its_own_expense_categories(): void
    {
        $communication = Departement::query()->where('slug', 'communication')->firstOrFail();
        $commercial = Departement::query()->where('slug', 'commercial')->firstOrFail();
        $commUser = User::factory()->create([
            'role' => 'super_admin',
            'departement_id' => $communication->id,
            'password' => 'password',
        ]);
        $salesUser = User::factory()->create([
            'role' => 'super_admin',
            'departement_id' => $commercial->id,
            'password' => 'password',
        ]);

        $budget = BudgetAnnuel::create([
            'annee' => 2026,
            'montant' => 1000000,
        ]);

        $this->actingAs($commUser)
            ->from(route('budget-annuels.show', $budget))
            ->post(route('budget-annuels.categories.store'), ['categorie_nom' => 'Transport'])
            ->assertRedirect(route('budget-annuels.show', $budget))
            ->assertSessionHas('categorie_creee');

        $this->actingAs($commUser);
        $this->assertTrue(DepenseCategorie::query()->where('nom', 'Transport')->exists());

        $this->actingAs($salesUser);
        $this->assertFalse(DepenseCategorie::query()->where('nom', 'Transport')->exists());
    }
}
