<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\EcoleAssiduite;
use App\Models\EcoleEnseignant;
use App\Models\EcoleGroupe;
use App\Models\EcoleMatiere;
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

        $this->post(route('ecole.matieres.store'), ['nom' => 'COM & PLAN'])->assertRedirect(route('ecole.matieres.index'));
        $this->post(route('ecole.matieres.store'), ['nom' => 'Trop tot'])->assertRedirect();
        $this->post(route('ecole.matieres.store'), ['nom' => 'COURS AUTRE SEMAINE'])->assertRedirect();
        $this->post(route('ecole.enseignants.store'), [
            'prenom' => 'Hery',
            'nom' => 'Rakoto',
        ])->assertRedirect(route('ecole.enseignants.index'));

        $com = EcoleMatiere::query()->where('nom', 'COM & PLAN')->firstOrFail();
        $tropTot = EcoleMatiere::query()->where('nom', 'Trop tot')->firstOrFail();
        $autre = EcoleMatiere::query()->where('nom', 'COURS AUTRE SEMAINE')->firstOrFail();
        $professeur = EcoleEnseignant::query()->firstOrFail();

        $jeudi = EcoleTimetable::monday()->addDays(3);

        $this->post(route('ecole.emplois.store'), [
            'ecole_groupe_id' => $b1->id,
            'date' => $jeudi->toDateString(),
            'heure_debut' => '08:00',
            'heure_fin' => '09:00',
            'ecole_matiere_id' => $com->id,
            'ecole_enseignant_id' => $professeur->id,
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
            'ecole_matiere_id' => $tropTot->id,
            'ecole_enseignant_id' => $professeur->id,
            'salle' => 'Ivato',
        ])->assertSessionHasErrors('heure_debut');

        $this->post(route('ecole.emplois.store'), [
            'ecole_groupe_id' => $b1->id,
            'date' => EcoleTimetable::monday()->addWeek()->toDateString(),
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
            'ecole_matiere_id' => $autre->id,
            'ecole_enseignant_id' => $professeur->id,
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
            ->assertSee('Hery Rakoto')
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
            ->assertDontSee('Hery Rakoto')
            ->assertDontSee('Grasse');
    }

    public function test_group_eye_shows_only_that_group_students(): void
    {
        $pedagogique = Departement::query()->where('slug', 'pedagogique')->firstOrFail();
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'departement_id' => $pedagogique->id,
            'password' => 'password',
        ]);

        $this->actingAs($admin)->withSession(['departement_actif_id' => $pedagogique->id]);

        $this->post(route('ecole.groupes.store'), ['nom' => 'B1 G1'])->assertRedirect();
        $this->post(route('ecole.groupes.store'), ['nom' => 'B2'])->assertRedirect();
        $b1 = EcoleGroupe::query()->where('nom', 'B1 G1')->firstOrFail();
        $b2 = EcoleGroupe::query()->where('nom', 'B2')->firstOrFail();

        $this->post(route('ecole.etudiants.store'), [
            'prenom' => 'Aina',
            'nom' => 'Rabe',
            'ecole_groupe_id' => $b1->id,
        ])->assertRedirect();
        $this->post(route('ecole.etudiants.store'), [
            'prenom' => 'Mialy',
            'nom' => 'Rakoto',
            'ecole_groupe_id' => $b2->id,
        ])->assertRedirect();

        $this->get(route('ecole.groupes.index'))
            ->assertOk()
            ->assertSee('Voir les étudiants')
            ->assertSee(route('ecole.groupes.show', $b1), false);

        $this->get(route('ecole.groupes.show', $b1))
            ->assertOk()
            ->assertSee('Résultats 1 to 1 de 1')
            ->assertSee('Aina Rabe')
            ->assertSee('Enregistrer une présence')
            ->assertSee('Exporter vers Excel')
            ->assertDontSee('Mialy Rakoto');

        $export = $this->get(route('ecole.groupes.export', $b1));
        $export->assertOk();
        $this->assertStringContainsString('Aina', $export->streamedContent());
        $this->assertStringNotContainsString('Mialy', $export->streamedContent());

        $this->post(route('ecole.groupes.presence', $b1))
            ->assertRedirect(route('ecole.groupes.show', $b1));

        $etudiant = $b1->etudiants()->where('prenom', 'Aina')->firstOrFail();
        $this->assertTrue(EcoleAssiduite::query()
            ->where('ecole_etudiant_id', $etudiant->id)
            ->whereDate('date', now()->toDateString())
            ->where('statut', 'present')
            ->exists());
        $this->assertFalse(EcoleAssiduite::query()
            ->where('ecole_etudiant_id', $b2->etudiants()->where('prenom', 'Mialy')->firstOrFail()->id)
            ->exists());
    }
}
