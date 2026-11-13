<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\EcoleGroupe;
use App\Models\User;
use App\Support\EcoleTimetable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EcoleDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');
        config(['app.url' => 'http://localhost']);
    }

    public function test_dashboard_shows_school_figures_and_the_selected_group_timetable(): void
    {
        $pedagogique = Departement::query()->where('slug', 'pedagogique')->firstOrFail();
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'departement_id' => $pedagogique->id,
            'password' => 'password',
        ]);

        $this->actingAs($admin)->withSession(['departement_actif_id' => $pedagogique->id]);

        $this->post(route('ecole.groupes.store'), ['nom' => 'B1'])->assertRedirect(route('ecole.groupes.index'));
        $this->post(route('ecole.groupes.store'), ['nom' => 'B2'])->assertRedirect(route('ecole.groupes.index'));

        $b1 = EcoleGroupe::query()->where('nom', 'B1')->firstOrFail();
        $b2 = EcoleGroupe::query()->where('nom', 'B2')->firstOrFail();

        $this->post(route('ecole.etudiants.store'), [
            'prenom' => 'Aina',
            'nom' => 'Rabe',
            'ecole_groupe_id' => $b1->id,
        ])->assertRedirect(route('ecole.etudiants.index'));

        $this->post(route('ecole.etudiants.store'), [
            'prenom' => 'Mialy',
            'nom' => 'Rasoanaivo',
            'ecole_groupe_id' => $b2->id,
        ])->assertRedirect();

        $etudiant = $b1->etudiants()->firstOrFail();

        $jeudi = EcoleTimetable::monday()->addDays(3);

        $this->post(route('ecole.emplois.store'), [
            'ecole_groupe_id' => $b1->id,
            'date' => $jeudi->toDateString(),
            'heure_debut' => '08:00',
            'heure_fin' => '09:00',
            'matiere' => 'COM & PLAN',
            'salle' => 'Grasse',
        ])->assertRedirect(route('ecole.emplois.index', [
            'groupe' => $b1->id,
            'semaine' => EcoleTimetable::monday()->toDateString(),
        ]));

        $this->post(route('ecole.emplois.store'), [
            'ecole_groupe_id' => $b1->id,
            'date' => EcoleTimetable::monday()->toDateString(),
            'heure_debut' => '07:30',
            'heure_fin' => '08:30',
            'matiere' => 'Trop tot',
            'salle' => 'Ivato',
        ])->assertSessionHasErrors('heure_debut');

        $this->post(route('ecole.emplois.store'), [
            'ecole_groupe_id' => $b1->id,
            'date' => EcoleTimetable::monday()->addWeek()->toDateString(),
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
            'matiere' => 'COURS AUTRE SEMAINE',
            'salle' => 'Lille',
        ])->assertRedirect();

        $this->post(route('ecole.absences.store'), [
            'ecole_etudiant_id' => $etudiant->id,
            'date' => now()->toDateString(),
            'statut' => 'present',
        ])->assertRedirect(route('ecole.absences.index', [
            'annee' => now()->year,
            'mois' => now()->month,
        ]));

        $this->post(route('ecole.absences.store'), [
            'ecole_etudiant_id' => $b2->etudiants()->firstOrFail()->id,
            'date' => now()->toDateString(),
            'statut' => 'absent',
        ])->assertRedirect();

        $this->actingAs($admin)
            ->withSession(['departement_actif_id' => $pedagogique->id])
            ->get(route('ecole.dashboard', ['groupe' => $b1->id]))
            ->assertOk()
            ->assertSee('Nombre étudiant')
            ->assertSee('Etudiants par groupe')
            ->assertSee('Statistique présence')
            ->assertSee('Statistique absence')
            ->assertSee('1 B1')
            ->assertSee('1 B2')
            ->assertSee('50 %')
            ->assertSee('Lundi')
            ->assertSee('Mardi')
            ->assertSee('Mercredi')
            ->assertSee('Jeudi')
            ->assertSee('Vendredi')
            ->assertSee('08h')
            ->assertSee('17h')
            ->assertSee('COM & PLAN')
            ->assertSee('Grasse')
            ->assertDontSee('Trop tot')
            ->assertDontSee('COURS AUTRE SEMAINE');

        $this->actingAs($admin)
            ->withSession(['departement_actif_id' => $pedagogique->id])
            ->get(route('ecole.dashboard', [
                'groupe' => $b1->id,
                'semaine' => EcoleTimetable::monday()->addWeek()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('COURS AUTRE SEMAINE')
            ->assertDontSee('COM & PLAN');

        $this->actingAs($admin)
            ->withSession(['departement_actif_id' => $pedagogique->id])
            ->get(route('ecole.dashboard', ['groupe' => $b2->id]))
            ->assertOk()
            ->assertDontSee('COM & PLAN')
            ->assertDontSee('Grasse');
    }
}
