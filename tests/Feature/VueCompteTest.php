<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\EcoleEnseignant;
use App\Models\EcoleEtudiant;
use App\Models\EcoleGroupe;
use App\Models\EcoleSeance;
use App\Models\Permission;
use App\Models\User;
use App\Support\EcoleTimetable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class VueCompteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');
        config(['app.url' => 'http://localhost']);
    }

    public function test_super_admin_can_preview_a_teacher_student_or_parent_dashboard(): void
    {
        $pedagogique = Departement::query()->where('slug', 'pedagogique')->firstOrFail();
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'departement_id' => $pedagogique->id,
            'password' => 'password',
        ]);
        $staff = User::factory()->create([
            'role' => 'responsable_communication',
            'departement_id' => $pedagogique->id,
            'password' => 'password',
        ]);
        $staff->permissions()->sync([Permission::query()->where('key', 'ecole.view')->value('id')]);
        $this->actingAs($admin)->withSession(['departement_actif_id' => $pedagogique->id]);

        $this->post(route('ecole.groupes.store'), ['nom' => 'B1'])->assertRedirect();
        $this->post(route('ecole.groupes.store'), ['nom' => 'B2'])->assertRedirect();
        $this->post(route('ecole.enseignants.store'), ['prenom' => 'Lorem', 'nom' => 'Test']);
        $this->post(route('ecole.enseignants.store'), ['prenom' => 'Nina', 'nom' => 'Rabe']);
        $b1 = EcoleGroupe::query()->where('nom', 'B1')->firstOrFail();
        $b2 = EcoleGroupe::query()->where('nom', 'B2')->firstOrFail();
        $lorem = EcoleEnseignant::query()->where('prenom', 'Lorem')->firstOrFail();
        $nina = EcoleEnseignant::query()->where('prenom', 'Nina')->firstOrFail();
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
        $this->post(route('ecole.etudiants.store'), [
            'prenom' => 'Aina',
            'nom' => 'Rabe',
            'ecole_groupe_id' => $b1->id,
            'creer_compte_parent' => '1',
            'parent_nom' => 'Maman Aina',
            'parent_email' => 'maman@example.test',
            'parent_password' => 'Secret12345',
        ])->assertRedirect();
        $etudiant = EcoleEtudiant::query()->where('prenom', 'Aina')->firstOrFail();
        $parent = User::query()->where('email', 'maman@example.test')->firstOrFail();

        $this->get(route('ecole.dashboard'))
            ->assertOk()
            ->assertSee('Compte enseignant')
            ->assertSee('Enseignants')
            ->assertSee('Étudiants')
            ->assertSee('Parents')
            ->assertSee('Lorem Test')
            ->assertSee('Aina Rabe')
            ->assertSee('Maman Aina');

        $this->get(route('ecole.compte-enseignant'))
            ->assertOk()
            ->assertSee('Choisissez un enseignant')
            ->assertSee('Lorem Test');

        $this->post(route('departement.switch'), ['vue' => 'enseignant:'.$lorem->id])
            ->assertRedirect(route('ecole.dashboard'));

        $this->get(route('ecole.dashboard'))
            ->assertOk()
            ->assertSee('Nombre étudiant')
            ->assertSee('ANGLAIS LOREM')
            ->assertDontSee('COURS NINA')
            ->assertDontSee('Gestion Etudiants');

        $this->get(route('ecole.emplois.index'))
            ->assertOk()
            ->assertSee('ANGLAIS LOREM')
            ->assertDontSee('COURS NINA')
            ->assertDontSee('Ajouter un créneau');

        $this->post(route('departement.switch'), ['vue' => 'etudiant:'.$etudiant->id])
            ->assertRedirect(route('ecole.dashboard'));
        $this->get(route('ecole.dashboard'))
            ->assertOk()
            ->assertSee('Total heure de cours')
            ->assertSee('ANGLAIS LOREM')
            ->assertDontSee('COURS NINA');

        $this->post(route('departement.switch'), ['vue' => 'parent:'.$parent->id])
            ->assertRedirect(route('ecole.dashboard'));
        $this->get(route('ecole.emplois.index'))
            ->assertOk()
            ->assertSee('ANGLAIS LOREM')
            ->assertDontSee('COURS NINA')
            ->assertDontSee('Ajouter un créneau');

        $this->actingAs($staff)
            ->withSession(['departement_actif_id' => $pedagogique->id])
            ->post(route('departement.switch'), ['vue' => 'enseignant:'.$lorem->id])
            ->assertForbidden();

        $this->actingAs($staff)
            ->get(route('ecole.compte-enseignant', ['enseignant' => $lorem->id]))
            ->assertOk()
            ->assertSee('ANGLAIS LOREM')
            ->assertDontSee('COURS NINA');
    }
}
