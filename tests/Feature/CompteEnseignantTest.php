<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\EcoleEnseignant;
use App\Models\EcoleGroupe;
use App\Models\EcoleSeance;
use App\Models\User;
use App\Support\EcoleTimetable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CompteEnseignantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');
        config(['app.url' => 'http://localhost']);
    }

    public function test_creating_a_teacher_can_open_a_school_only_account(): void
    {
        $pedagogique = Departement::query()->where('slug', 'pedagogique')->firstOrFail();
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'departement_id' => $pedagogique->id,
            'password' => 'password',
        ]);
        $this->actingAs($admin)->withSession(['departement_actif_id' => $pedagogique->id]);

        $this->get(route('ecole.enseignants.index'))
            ->assertOk()
            ->assertSee('Créer un compte enseignant')
            ->assertSee('Générer un mot de passe');

        $this->post(route('ecole.groupes.store'), ['nom' => 'B1'])->assertRedirect();
        $this->post(route('ecole.groupes.store'), ['nom' => 'B2'])->assertRedirect();
        $b1 = EcoleGroupe::query()->where('nom', 'B1')->firstOrFail();
        $b2 = EcoleGroupe::query()->where('nom', 'B2')->firstOrFail();

        $this->post(route('ecole.enseignants.store'), [
            'prenom' => 'Lorem',
            'nom' => 'Test',
            'creer_compte_enseignant' => '1',
            'enseignant_email' => 'lorem@example.test',
            'enseignant_password' => 'Secret12345',
        ])->assertRedirect(route('ecole.enseignants.index'))
            ->assertSessionHas('success', 'Enseignant ajouté. Compte enseignant créé.');

        $lorem = EcoleEnseignant::query()->where('prenom', 'Lorem')->firstOrFail();
        $compte = User::query()->where('email', 'lorem@example.test')->firstOrFail();
        $this->assertTrue($compte->isEnseignant());
        $this->assertTrue(Hash::check('Secret12345', $compte->password));
        $this->assertSame($compte->id, $lorem->user_id);
        $this->assertTrue($compte->canAccess('ecole.view'));
        $this->assertFalse($compte->canAccess('dashboard.view'));

        $this->get(route('users.create'))
            ->assertOk()
            ->assertSee('Étudiant')
            ->assertSee('Parent')
            ->assertSee('Enseignant');

        $this->get(route('ecole.compte-enseignant'))
            ->assertOk()
            ->assertSee('lorem@example.test')
            ->assertSee('Réinitialiser');

        $this->post(route('ecole.compte-enseignant.password', $lorem), [
            'password' => 'Nouveau123',
        ])->assertRedirect(route('ecole.compte-enseignant'));
        $this->assertTrue(Hash::check('Nouveau123', $compte->fresh()->password));

        $this->post(route('ecole.enseignants.store'), [
            'prenom' => 'Nina',
            'nom' => 'Rabe',
        ])->assertRedirect();
        $nina = EcoleEnseignant::query()->where('prenom', 'Nina')->firstOrFail();
        $this->assertNull($nina->user_id);

        $jeudi = EcoleTimetable::monday()->addDays(3);
        EcoleSeance::create([
            'ecole_groupe_id' => $b1->id,
            'ecole_enseignant_id' => $lorem->id,
            'date' => $jeudi->toDateString(),
            'jour' => $jeudi->isoWeekday(),
            'heure_debut' => '08:00',
            'heure_fin' => '09:00',
            'matiere' => 'ANGLAIS LOREM',
            'professeur' => $lorem->nom_complet,
        ]);
        EcoleSeance::create([
            'ecole_groupe_id' => $b2->id,
            'ecole_enseignant_id' => $nina->id,
            'date' => $jeudi->toDateString(),
            'jour' => $jeudi->isoWeekday(),
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
            'matiere' => 'COURS NINA',
            'professeur' => $nina->nom_complet,
        ]);

        auth()->logout();
        $this->post('/login', [
            'email' => 'lorem@example.test',
            'password' => 'Nouveau123',
        ])->assertRedirect(route('ecole.dashboard'));

        $this->get(route('ecole.dashboard'))
            ->assertOk()
            ->assertSee('Nombre étudiant')
            ->assertSee('ANGLAIS LOREM')
            ->assertDontSee('COURS NINA')
            ->assertDontSee('Discussion')
            ->assertDontSee('Budget & Dépenses')
            ->assertDontSee('Campagnes')
            ->assertDontSee('Gestion Etudiants');

        $this->get(route('ecole.emplois.index'))
            ->assertOk()
            ->assertSee('ANGLAIS LOREM')
            ->assertDontSee('COURS NINA')
            ->assertDontSee('Ajouter un créneau');

        $this->get(route('dashboard'))->assertForbidden();
        $this->get(route('ecole.etudiants.index'))->assertForbidden();
    }
}
