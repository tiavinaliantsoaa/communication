<?php

namespace Tests\Feature;

use App\Models\CrmCandidate;
use App\Models\Departement;
use App\Models\EcoleEtudiant;
use App\Models\EcoleGroupe;
use App\Models\User;
use App\Support\AnneeUniversitaire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EcoleAnneeCrmTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');
        config(['app.url' => 'http://localhost']);
    }

    public function test_student_form_links_an_enrolled_crm_candidate_to_the_selected_year_group(): void
    {
        $pedagogique = Departement::query()->where('slug', 'pedagogique')->firstOrFail();
        $communication = Departement::query()->where('slug', 'communication')->firstOrFail();
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'departement_id' => $pedagogique->id,
            'password' => 'password',
        ]);

        $this->actingAs($admin)->withSession(['departement_actif_id' => $pedagogique->id]);

        $future = AnneeUniversitaire::options()[1];

        $this->get(route('ecole.etudiants.index'))
            ->assertOk()
            ->assertSee('Année Universitaire')
            ->assertSee(AnneeUniversitaire::current())
            ->assertSee($future)
            ->assertSee('Actuelle');

        $this->actingAs($admin)
            ->withSession(['departement_actif_id' => $communication->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Année Universitaire');

        $this->actingAs($admin)->withSession(['departement_actif_id' => $pedagogique->id]);

        $this->post(route('ecole.groupes.store'), ['nom' => 'B1 G1'])->assertRedirect();
        $groupe = EcoleGroupe::query()->where('nom', 'B1 G1')->firstOrFail();
        $this->assertSame(AnneeUniversitaire::current(), $groupe->annee_universitaire);

        $this->get(route('ecole.etudiants.index'))
            ->assertOk()
            ->assertSee('Étudiant via CRM');

        $inscrit = CrmCandidate::create([
            'prenom' => 'Aina',
            'nom' => 'Rabe',
            'telephone' => '0341970012',
            'programme' => 'B1',
            'statut' => 'inscrit',
            'abandon' => false,
        ]);
        $prospect = CrmCandidate::create([
            'prenom' => 'Mialy',
            'nom' => 'Rasoanaivo',
            'statut' => 'prospect',
            'abandon' => false,
        ]);
        $abandonne = CrmCandidate::create([
            'prenom' => 'Hery',
            'nom' => 'Rakoto',
            'statut' => 'inscrit',
            'abandon' => true,
        ]);

        $this->getJson(route('ecole.etudiants.crm', ['q' => 'Rabe']))
            ->assertOk()
            ->assertJsonFragment(['id' => $inscrit->id, 'prenom' => 'Aina', 'nom' => 'Rabe'])
            ->assertJsonMissing(['id' => $prospect->id])
            ->assertJsonMissing(['id' => $abandonne->id]);

        $this->getJson(route('ecole.etudiants.crm', ['q' => '341970012']))
            ->assertOk()
            ->assertJsonFragment(['id' => $inscrit->id]);

        $this->post(route('ecole.etudiants.store'), [
            'prenom' => 'Aina',
            'nom' => 'Rabe',
            'ecole_groupe_id' => $groupe->id,
            'crm_candidate_id' => $prospect->id,
        ])->assertNotFound();

        $this->post(route('ecole.etudiants.store'), [
            'prenom' => 'Aina',
            'nom' => 'Rabe',
            'ecole_groupe_id' => $groupe->id,
            'crm_candidate_id' => $inscrit->id,
        ])->assertRedirect(route('ecole.etudiants.index'));

        $this->assertDatabaseHas('ecole_etudiants', [
            'crm_candidate_id' => $inscrit->id,
            'ecole_groupe_id' => $groupe->id,
            'prenom' => 'Aina',
            'nom' => 'Rabe',
        ]);
        $this->assertDatabaseHas('crm_notes', [
            'crm_candidate_id' => $inscrit->id,
            'content' => 'Inscris dans Groupe B1 G1',
        ]);

        $this->get(route('crm.candidats.show', $inscrit))
            ->assertOk()
            ->assertSee('Inscris dans Groupe B1 G1');

        $this->get(route('crm.candidats.show', ['candidat' => $inscrit, 'tab' => 'notes']))
            ->assertOk()
            ->assertSee('Inscris dans Groupe B1 G1');

        $this->getJson(route('ecole.etudiants.crm', ['q' => 'Rabe']))
            ->assertOk()
            ->assertJsonMissing(['id' => $inscrit->id]);

        $this->post(route('ecole.etudiants.store'), [
            'prenom' => 'Aina',
            'nom' => 'Rabe',
            'ecole_groupe_id' => $groupe->id,
            'crm_candidate_id' => $inscrit->id,
        ])->assertSessionHasErrors('crm_candidate_id');

        $this->post(route('ecole.annee'), ['annee_universitaire' => $future])->assertRedirect();

        $this->get(route('ecole.groupes.index'))
            ->assertOk()
            ->assertDontSee('B1 G1')
            ->assertSee($future)
            ->assertSee(now()->locale('fr')->isoFormat('MMMM YYYY'));
        $this->get(route('ecole.etudiants.index'))
            ->assertOk()
            ->assertDontSee('Aina Rabe');

        $this->post(route('ecole.groupes.store'), ['nom' => 'B1 G1'])->assertRedirect();
        $this->assertSame(2, EcoleGroupe::withoutGlobalScope('annee')->where('nom', 'B1 G1')->count());

        $this->post(route('ecole.groupes.store'), ['nom' => 'B2 G1'])->assertRedirect();
        $futur = EcoleGroupe::query()->where('nom', 'B2 G1')->firstOrFail();
        $this->assertSame($future, $futur->annee_universitaire);

        $this->getJson(route('ecole.etudiants.crm', ['q' => 'Rabe']))
            ->assertOk()
            ->assertJsonFragment(['id' => $inscrit->id]);

        $this->post(route('ecole.etudiants.store'), [
            'prenom' => 'Aina',
            'nom' => 'Rabe',
            'ecole_groupe_id' => $futur->id,
            'crm_candidate_id' => $inscrit->id,
        ])->assertRedirect(route('ecole.etudiants.index'));

        $this->assertDatabaseHas('crm_notes', [
            'crm_candidate_id' => $inscrit->id,
            'content' => 'Inscris dans Groupe B2 G1',
        ]);

        $this->post(route('ecole.annee'), ['annee_universitaire' => AnneeUniversitaire::current()])->assertRedirect();

        $etudiant = EcoleEtudiant::query()->where('crm_candidate_id', $inscrit->id)->firstOrFail();
        $this->delete(route('ecole.etudiants.destroy', $etudiant))->assertRedirect(route('ecole.etudiants.index'));

        $this->assertDatabaseMissing('crm_notes', [
            'crm_candidate_id' => $inscrit->id,
            'content' => 'Inscris dans Groupe B1 G1',
        ]);
        $this->assertDatabaseHas('crm_notes', [
            'crm_candidate_id' => $inscrit->id,
            'content' => 'Inscris dans Groupe B2 G1',
        ]);

        $this->get(route('crm.candidats.show', $inscrit))
            ->assertOk()
            ->assertSee('Inscris dans Groupe B2 G1')
            ->assertDontSee('Inscris dans Groupe B1 G1');
    }
}
