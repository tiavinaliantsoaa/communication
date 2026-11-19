<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\EcoleAssiduite;
use App\Models\EcoleEtudiant;
use App\Models\EcoleGroupe;
use App\Models\EcoleSeance;
use App\Models\User;
use App\Support\EcoleTimetable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ParentCompteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');
        config(['app.url' => 'http://localhost']);
    }

    public function test_student_form_offers_a_parent_account(): void
    {
        $this->actingAs($this->equipe());

        $this->post(route('ecole.groupes.store'), ['nom' => 'B1'])->assertRedirect();

        $this->get(route('ecole.etudiants.index'))
            ->assertOk()
            ->assertSee('Créer un compte parent')
            ->assertSee('Générer un mot de passe')
            ->assertSee('Copier le mot de passe')
            ->assertSee('Nom parent');
    }

    public function test_student_without_the_checkbox_has_no_parent_account(): void
    {
        $this->actingAs($this->equipe());
        $this->post(route('ecole.groupes.store'), ['nom' => 'B1'])->assertRedirect();
        $groupe = EcoleGroupe::query()->where('nom', 'B1')->firstOrFail();

        $this->post(route('ecole.etudiants.store'), [
            'prenom' => 'Aina',
            'nom' => 'Rabe',
            'ecole_groupe_id' => $groupe->id,
        ])->assertRedirect(route('ecole.etudiants.index'));

        $this->assertSame(0, User::query()->where('role', 'parent')->count());
        $this->assertNull(EcoleEtudiant::query()->firstOrFail()->parent_user_id);
    }

    public function test_parent_sees_only_their_child_dashboard_timetable_and_absences(): void
    {
        $this->actingAs($this->equipe());
        $this->post(route('ecole.groupes.store'), ['nom' => 'B1'])->assertRedirect();
        $this->post(route('ecole.groupes.store'), ['nom' => 'B2'])->assertRedirect();
        $b1 = EcoleGroupe::query()->where('nom', 'B1')->firstOrFail();
        $b2 = EcoleGroupe::query()->where('nom', 'B2')->firstOrFail();

        $this->post(route('ecole.etudiants.store'), [
            'prenom' => 'Claire',
            'nom' => 'Andiva',
            'ecole_groupe_id' => $b1->id,
            'creer_compte_parent' => '1',
            'parent_nom' => 'Marie Andiva',
            'parent_email' => 'parent@example.test',
            'parent_password' => 'Secret12345',
        ])->assertRedirect(route('ecole.etudiants.index'))
            ->assertSessionHas('success', 'Étudiant ajouté. Compte parent créé.');

        $this->post(route('ecole.etudiants.store'), [
            'prenom' => 'Mialy',
            'nom' => 'Rasoanaivo',
            'ecole_groupe_id' => $b2->id,
        ])->assertRedirect();

        $claire = EcoleEtudiant::query()->where('prenom', 'Claire')->firstOrFail();
        $mialy = EcoleEtudiant::query()->where('prenom', 'Mialy')->firstOrFail();
        $parent = User::query()->where('email', 'parent@example.test')->firstOrFail();

        $this->assertTrue($parent->isParent());
        $this->assertTrue(Hash::check('Secret12345', $parent->password));
        $this->assertTrue($parent->canAccess('ecole.view'));
        $this->assertFalse($parent->canAccess('ecole.update'));
        $this->assertSame($parent->id, $claire->parent_user_id);

        $jeudi = EcoleTimetable::monday()->addDays(3);
        EcoleSeance::create([
            'ecole_groupe_id' => $b1->id,
            'date' => $jeudi->toDateString(),
            'jour' => 4,
            'heure_debut' => '08:00',
            'heure_fin' => '09:00',
            'matiere' => 'COM & PLAN',
            'professeur' => 'Hery',
        ]);
        EcoleSeance::create([
            'ecole_groupe_id' => $b2->id,
            'date' => $jeudi->toDateString(),
            'jour' => 4,
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
            'matiere' => 'COURS AUTRE',
            'professeur' => 'Autre',
        ]);
        EcoleAssiduite::create([
            'ecole_etudiant_id' => $claire->id,
            'date' => $jeudi->toDateString(),
            'statut' => 'absent',
        ]);
        EcoleAssiduite::create([
            'ecole_etudiant_id' => $mialy->id,
            'date' => $jeudi->toDateString(),
            'statut' => 'retard',
        ]);

        auth()->logout();
        $this->post('/login', [
            'email' => 'parent@example.test',
            'password' => 'Secret12345',
        ])->assertRedirect(route('ecole.dashboard'));

        $this->get(route('ecole.dashboard'))
            ->assertOk()
            ->assertSee('COM & PLAN')
            ->assertSee('B1')
            ->assertSee('Mon enfant')
            ->assertSee('Voir l’emploi du temps de B1')
            ->assertDontSee('COURS AUTRE')
            ->assertDontSee('B2')
            ->assertDontSee('Gestion Etudiants')
            ->assertDontSee('Matière')
            ->assertDontSee('Gérer l’emploi du temps');

        $this->get(route('ecole.etudiants.show', $claire))
            ->assertOk()
            ->assertSee('Claire Andiva')
            ->assertSee('Retour au dashboard')
            ->assertDontSee('Modifier');

        $this->get(route('ecole.etudiants.show', $mialy))->assertNotFound();
        $this->get(route('ecole.absences.index'))
            ->assertOk()
            ->assertSee('Claire Andiva')
            ->assertDontSee('Mialy Rasoanaivo')
            ->assertDontSee('Nouveau pointage')
            ->assertDontSee('Supprimer');
        $this->get(route('ecole.emplois.index'))
            ->assertOk()
            ->assertSee('COM & PLAN')
            ->assertDontSee('Ajouter un créneau')
            ->assertDontSee('COURS AUTRE');
        $this->get(route('ecole.matieres.index'))->assertForbidden();
        $this->get(route('ecole.etudiants.index'))->assertForbidden();
        $this->get(route('ecole.etudiants.edit', $claire))->assertForbidden();
        $this->post(route('ecole.absences.store'), [
            'ecole_etudiant_id' => $claire->id,
            'date' => $jeudi->toDateString(),
            'statut' => 'present',
        ])->assertForbidden();
    }

    public function test_the_same_parent_email_links_another_child(): void
    {
        $this->actingAs($this->equipe());
        $this->post(route('ecole.groupes.store'), ['nom' => 'B1'])->assertRedirect();
        $groupe = EcoleGroupe::query()->where('nom', 'B1')->firstOrFail();

        $compte = [
            'creer_compte_parent' => '1',
            'parent_nom' => 'Marie Andiva',
            'parent_email' => 'parent@example.test',
            'parent_password' => 'Secret12345',
        ];
        $this->post(route('ecole.etudiants.store'), $compte + [
            'prenom' => 'Claire',
            'nom' => 'Andiva',
            'ecole_groupe_id' => $groupe->id,
        ])->assertSessionHas('success', 'Étudiant ajouté. Compte parent créé.');

        $this->post(route('ecole.etudiants.store'), array_merge($compte, [
            'prenom' => 'Lova',
            'nom' => 'Andiva',
            'ecole_groupe_id' => $groupe->id,
            'parent_password' => 'AutreMotDePasse1',
        ]))->assertSessionHas('success', 'Étudiant ajouté. Compte parent associé.');

        $parent = User::query()->where('role', 'parent')->get();
        $this->assertCount(1, $parent);
        $this->assertTrue(Hash::check('AutreMotDePasse1', $parent->first()->password));
        $this->assertSame(2, EcoleEtudiant::query()->where('parent_user_id', $parent->first()->id)->count());
    }

    public function test_an_email_used_by_another_account_is_rejected(): void
    {
        $equipe = $this->equipe();
        $this->actingAs($equipe);
        $this->post(route('ecole.groupes.store'), ['nom' => 'B1'])->assertRedirect();
        $groupe = EcoleGroupe::query()->where('nom', 'B1')->firstOrFail();

        $this->post(route('ecole.etudiants.store'), [
            'prenom' => 'Claire',
            'nom' => 'Andiva',
            'ecole_groupe_id' => $groupe->id,
            'creer_compte_parent' => '1',
            'parent_nom' => 'Marie Andiva',
            'parent_email' => $equipe->email,
            'parent_password' => 'Secret12345',
        ])->assertSessionHasErrors('parent_email');

        $this->assertSame(0, EcoleEtudiant::query()->count());
        $this->assertSame(0, User::query()->where('role', 'parent')->count());
    }

    private function equipe(): User
    {
        $pedagogique = Departement::query()->where('slug', 'pedagogique')->firstOrFail();

        return User::factory()->create([
            'role' => 'super_admin',
            'departement_id' => $pedagogique->id,
            'password' => 'password',
        ]);
    }
}
