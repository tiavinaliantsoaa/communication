<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\EcoleAssiduite;
use App\Models\EcoleEnseignant;
use App\Models\EcoleGroupe;
use App\Models\EcoleSeance;
use App\Models\Permission;
use App\Services\AccessService;
use App\Models\EcoleMatiere;
use App\Models\EcoleSalle;
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
        $this->post(route('ecole.parametres.salles.store'), [
            'nom' => 'Grasse',
            'capacite' => 30,
        ])->assertRedirect(route('ecole.parametres.index'));
        $grasse = EcoleSalle::query()->where('nom', 'Grasse')->firstOrFail();

        $jeudi = EcoleTimetable::monday()->addDays(3);

        $this->post(route('ecole.emplois.store'), [
            'ecole_groupe_id' => $b1->id,
            'date' => $jeudi->toDateString(),
            'heure_debut' => '08:00',
            'heure_fin' => '09:00',
            'ecole_matiere_id' => $com->id,
            'ecole_enseignant_id' => $professeur->id,
            'ecole_salle_id' => $grasse->id,
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

    public function test_student_profile_is_visible_and_only_the_pedagogical_team_can_edit_it(): void
    {
        $pedagogique = Departement::query()->where('slug', 'pedagogique')->firstOrFail();
        $communication = Departement::query()->where('slug', 'communication')->firstOrFail();
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'departement_id' => $pedagogique->id,
            'password' => 'password',
        ]);
        $viewer = User::factory()->create([
            'role' => 'responsable_communication',
            'departement_id' => $pedagogique->id,
            'password' => 'password',
        ]);
        $editor = User::factory()->create([
            'role' => 'responsable_communication',
            'departement_id' => $pedagogique->id,
            'password' => 'password',
        ]);
        $commercial = User::factory()->create([
            'role' => 'responsable_communication',
            'departement_id' => $communication->id,
            'password' => 'password',
        ]);
        $viewId = Permission::query()->where('key', 'ecole.view')->value('id');
        $viewer->permissions()->sync([$viewId]);
        $commercial->permissions()->sync([$viewId]);
        AccessService::grantPedagogiqueMenus($editor);

        $this->actingAs($admin)->withSession(['departement_actif_id' => $pedagogique->id]);
        $this->post(route('ecole.groupes.store'), ['nom' => 'B1 G1'])->assertRedirect();
        $groupe = EcoleGroupe::query()->where('nom', 'B1 G1')->firstOrFail();
        $this->post(route('ecole.etudiants.store'), [
            'prenom' => 'Claire',
            'nom' => 'Andiva',
            'ecole_groupe_id' => $groupe->id,
        ])->assertRedirect();
        $etudiant = $groupe->etudiants()->where('prenom', 'Claire')->firstOrFail();
        $etudiant->update(['date_naissance' => '2005-12-17']);

        EcoleSeance::create([
            'ecole_groupe_id' => $groupe->id,
            'date' => EcoleTimetable::monday()->toDateString(),
            'jour' => 1,
            'heure_debut' => '08:00',
            'heure_fin' => '09:00',
            'matiere' => 'COM & PLAN',
            'professeur' => 'Hery Rakoto',
            'salle' => 'Grasse',
        ]);

        $this->get(route('ecole.groupes.show', $groupe))
            ->assertOk()
            ->assertSee(route('ecole.etudiants.show', $etudiant), false);

        $this->actingAs($viewer)
            ->get(route('ecole.etudiants.show', $etudiant))
            ->assertOk()
            ->assertSee('Claire Andiva')
            ->assertSee('Information générale')
            ->assertSee('20 années, 11 mois')
            ->assertSee('COM & PLAN')
            ->assertDontSee(route('ecole.etudiants.edit', $etudiant), false);

        $this->actingAs($viewer)->put(route('ecole.etudiants.update', $etudiant), [
            'prenom' => 'Claire',
            'nom' => 'Andiva',
            'ecole_groupe_id' => $groupe->id,
            'email' => 'claire.andiva@escm.mg',
        ])->assertForbidden();

        $this->actingAs($commercial)
            ->get(route('ecole.etudiants.show', $etudiant))
            ->assertNotFound();

        $this->actingAs($editor)->put(route('ecole.etudiants.update', $etudiant), [
            'prenom' => 'Claire',
            'nom' => 'Andiva',
            'nom_officiel' => 'ANDIVA Claire Mariame',
            'palier' => 'Licence 1',
            'ecole_groupe_id' => $groupe->id,
            'email' => 'claire.andiva@escm.mg',
            'matricule' => 'E26-011',
            'domicile' => 'Antananarivo',
        ])->assertRedirect(route('ecole.etudiants.show', $etudiant));

        $this->actingAs($editor)
            ->get(route('ecole.etudiants.show', $etudiant))
            ->assertOk()
            ->assertSee('Modifier')
            ->assertSee('ANDIVA Claire Mariame')
            ->assertSee('Licence 1')
            ->assertSee('claire.andiva@escm.mg')
            ->assertSee('E26-011')
            ->assertSee('Antananarivo')
            ->assertSee('2026-2027: B1 G1');
    }

    public function test_a_classroom_is_refused_when_the_group_is_larger_than_its_capacity(): void
    {
        $pedagogique = Departement::query()->where('slug', 'pedagogique')->firstOrFail();
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'departement_id' => $pedagogique->id,
            'password' => 'password',
        ]);
        $this->actingAs($admin)->withSession(['departement_actif_id' => $pedagogique->id]);

        $this->post(route('ecole.parametres.salles.store'), [
            'nom' => 'Grasse',
            'capacite' => 1,
        ])->assertRedirect(route('ecole.parametres.index'));

        $this->get(route('ecole.parametres.index'))
            ->assertOk()
            ->assertSee('Liste des salles de classe')
            ->assertSee('Grasse')
            ->assertSee('1 étudiant');

        $this->post(route('ecole.groupes.store'), ['nom' => 'B1'])->assertRedirect();
        $groupe = EcoleGroupe::query()->where('nom', 'B1')->firstOrFail();
        $this->post(route('ecole.etudiants.store'), ['prenom' => 'Aina', 'nom' => 'Rabe', 'ecole_groupe_id' => $groupe->id]);
        $this->post(route('ecole.etudiants.store'), ['prenom' => 'Mialy', 'nom' => 'Rasoanaivo', 'ecole_groupe_id' => $groupe->id]);
        $this->post(route('ecole.matieres.store'), ['nom' => 'COM']);
        $this->post(route('ecole.enseignants.store'), ['prenom' => 'Hery', 'nom' => 'Rakoto']);

        $salle = EcoleSalle::query()->where('nom', 'Grasse')->firstOrFail();
        $jeudi = EcoleTimetable::monday()->addDays(3);

        $this->get(route('ecole.emplois.index', ['groupe' => $groupe->id]))
            ->assertOk()
            ->assertSee('Grasse — 1 étudiant');

        $this->from(route('ecole.emplois.index', ['groupe' => $groupe->id]))
            ->post(route('ecole.emplois.store'), [
            'ecole_groupe_id' => $groupe->id,
            'date' => $jeudi->toDateString(),
            'heure_debut' => '08:00',
            'heure_fin' => '09:00',
            'ecole_matiere_id' => EcoleMatiere::query()->where('nom', 'COM')->value('id'),
            'ecole_enseignant_id' => EcoleEnseignant::query()->value('id'),
            'ecole_salle_id' => $salle->id,
        ])->assertRedirect(route('ecole.emplois.index', ['groupe' => $groupe->id]))
            ->assertSessionHasErrors('ecole_salle_id');

        $this->get(route('ecole.emplois.index', ['groupe' => $groupe->id]))
            ->assertSee('Incompatibilité : le groupe B1 compte 2 élèves, la salle Grasse ne peut accueillir que 1 personne.');

        $this->assertSame(0, EcoleSeance::query()->count());

        $salle->update(['capacite' => 2]);

        $this->post(route('ecole.emplois.store'), [
            'ecole_groupe_id' => $groupe->id,
            'date' => $jeudi->toDateString(),
            'heure_debut' => '08:00',
            'heure_fin' => '09:00',
            'ecole_matiere_id' => EcoleMatiere::query()->where('nom', 'COM')->value('id'),
            'ecole_enseignant_id' => EcoleEnseignant::query()->value('id'),
            'ecole_salle_id' => $salle->id,
        ])->assertRedirect();

        $this->assertSame('Grasse', EcoleSeance::query()->firstOrFail()->salle);
    }
}
