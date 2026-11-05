<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\BudgetAnnuel;
use App\Models\Departement;
use App\Models\Depense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class DashboardDirectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');
        config(['app.url' => 'http://localhost']);
    }

    public function test_direction_dashboard_mixes_departments_and_others_stay_scoped(): void
    {
        $communication = Departement::query()->where('slug', 'communication')->firstOrFail();
        $commercial = Departement::query()->where('slug', 'commercial')->firstOrFail();
        $pedagogique = Departement::query()->where('slug', 'pedagogique')->firstOrFail();
        $direction = Departement::query()->create([
            'nom' => 'Direction',
            'slug' => 'direction',
            'is_system' => false,
        ]);

        $annee = (int) now()->year;
        $mois = (int) now()->month;
        $date = now()->startOfMonth()->toDateString();

        $this->annuel($communication->id, $annee, 120000);
        $this->annuel($commercial->id, $annee, 34000);
        $this->annuel($pedagogique->id, $annee, 56000);
        $this->mensuel($communication->id, $annee, $mois, 4000);
        $this->mensuel($commercial->id, $annee, $mois, 7700);
        $this->mensuel($pedagogique->id, $annee, $mois, 9100);
        $this->depense($communication->id, 'Flyer communication', 1800, $date);
        $this->depense($pedagogique->id, 'Cahier pedagogique unique', 600, $date);

        $admin = User::factory()->create([
            'role' => 'super_admin',
            'departement_id' => $communication->id,
            'password' => 'password',
        ]);

        $this->actingAs($admin)
            ->withSession(['departement_actif_id' => $communication->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Vue d\'ensemble du service communication')
            ->assertSee('120 000 Ar')
            ->assertSee('4 000 Ar')
            ->assertSee('Flyer communication')
            ->assertDontSee('210 000 Ar')
            ->assertDontSee('Tous les départements')
            ->assertDontSee('Cahier pedagogique unique')
            ->assertDontSee('Commercial ·');

        $this->actingAs($admin)
            ->withSession(['departement_actif_id' => $direction->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Vue d\'ensemble de tous les départements')
            ->assertSee('210 000 Ar')
            ->assertSee('34 000 Ar')
            ->assertSee('120 000 Ar')
            ->assertSee('56 000 Ar')
            ->assertSee('26 300 Ar')
            ->assertSee('Restant :')
            ->assertDontSee('7 700 Ar')
            ->assertDontSee('boosts actifs')
            ->assertDontSee('data-evolution-key="'.$direction->id.'"', false)
            ->assertSee('Tous les départements')
            ->assertSee('Commercial')
            ->assertSee('Communication')
            ->assertSee('Pédagogique')
            ->assertSee('Cahier pedagogique unique')
            ->assertSee('Direction — Aucun budget mensuel');
    }

    private function annuel(int $departementId, int $annee, float $montant): void
    {
        $row = new BudgetAnnuel(['annee' => $annee, 'montant' => $montant]);
        $row->departement_id = $departementId;
        $row->save();
    }

    private function mensuel(int $departementId, int $annee, int $mois, float $montant): void
    {
        $row = new Budget(['annee' => $annee, 'mois' => $mois, 'montant' => $montant]);
        $row->departement_id = $departementId;
        $row->save();
    }

    private function depense(int $departementId, string $objet, float $montant, string $date): void
    {
        $row = new Depense([
            'fournisseur' => 'Fournisseur test',
            'objet' => $objet,
            'montant' => $montant,
            'statut' => 'paye',
            'date_depense' => $date,
        ]);
        $row->departement_id = $departementId;
        $row->save();
    }
}
