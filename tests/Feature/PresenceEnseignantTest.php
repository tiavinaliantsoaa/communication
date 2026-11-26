<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\EcoleEnseignant;
use App\Models\EcoleEtudiant;
use App\Models\EcoleGroupe;
use App\Models\EcolePresence;
use App\Models\EcoleSeance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PresenceEnseignantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');
        config(['app.url' => 'http://localhost']);
    }

    public function test_a_teacher_records_attendance_only_once_the_course_has_started(): void
    {
        $pedagogique = Departement::query()->where('slug', 'pedagogique')->firstOrFail();
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'departement_id' => $pedagogique->id,
            'password' => 'password',
        ]);
        $this->actingAs($admin)->withSession(['departement_actif_id' => $pedagogique->id]);
        $this->post(route('ecole.groupes.store'), ['nom' => 'B1 G1'])->assertRedirect();
        $groupe = EcoleGroupe::query()->where('nom', 'B1 G1')->firstOrFail();
        $this->post(route('ecole.etudiants.store'), [
            'prenom' => 'Claire',
            'nom' => 'Andiva',
            'ecole_groupe_id' => $groupe->id,
            'creer_compte_parent' => '1',
            'parent_nom' => 'Marie Andiva',
            'parent_email' => 'marie@example.test',
            'parent_password' => 'Secret12345',
        ])->assertRedirect();
        $this->post(route('ecole.enseignants.store'), [
            'prenom' => 'Lorem',
            'nom' => 'Test',
            'creer_compte_enseignant' => '1',
            'enseignant_email' => 'lorem@example.test',
            'enseignant_password' => 'Secret12345',
        ])->assertRedirect();

        $enseignant = EcoleEnseignant::query()->where('prenom', 'Lorem')->firstOrFail();
        $etudiant = EcoleEtudiant::query()->where('prenom', 'Claire')->firstOrFail();
        $seance = EcoleSeance::create([
            'ecole_groupe_id' => $groupe->id,
            'ecole_enseignant_id' => $enseignant->id,
            'date' => '2026-11-26',
            'jour' => 4,
            'heure_debut' => '14:00',
            'heure_fin' => '15:00',
            'matiere' => 'ANGLAIS',
            'professeur' => $enseignant->nom_complet,
        ]);

        auth()->logout();
        $this->post('/login', [
            'email' => 'lorem@example.test',
            'password' => 'Secret12345',
        ])->assertRedirect(route('ecole.dashboard'));

        Carbon::setTestNow(Carbon::parse('2026-11-26 10:00:00'));
        try {
            $this->get(route('ecole.presence.index'))
                ->assertOk()
                ->assertSee('Présence')
                ->assertSee('Claire Andiva')
                ->assertSee('La présence pourra être enregistrée')
                ->assertDontSee('Enregistrer la présence');

            $this->post(route('ecole.presence.store'), [
                'ecole_seance_id' => $seance->id,
                'presences' => [$etudiant->id => ['statut' => 'present']],
            ])->assertForbidden();

            Carbon::setTestNow(Carbon::parse('2026-11-26 14:10:00'));
            $this->post(route('ecole.presence.store'), [
                'ecole_seance_id' => $seance->id,
                'presences' => [$etudiant->id => ['statut' => 'absent']],
            ])->assertRedirect();
        } finally {
            Carbon::setTestNow();
        }

        $this->assertSame('absent', EcolePresence::query()->where('ecole_etudiant_id', $etudiant->id)->value('statut'));

        auth()->logout();
        $this->post('/login', [
            'email' => 'marie@example.test',
            'password' => 'Secret12345',
        ]);
        Carbon::setTestNow(Carbon::parse('2026-11-26 16:00:00'));
        try {
            $this->get(route('ecole.dashboard'))
                ->assertOk()
                ->assertSee('Total heure absence')
                ->assertSee('1 h');
        } finally {
            Carbon::setTestNow();
        }
    }
}
