<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\OnlineTest;
use App\Models\OnlineTestInvitation;
use App\Models\OnlineTestQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class OnlineTestDepartementScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');
        config(['app.url' => 'http://localhost']);
    }

    public function test_other_departments_cannot_see_or_delete_exam_links(): void
    {
        $communication = Departement::query()->where('slug', 'communication')->firstOrFail();
        $commercial = Departement::query()->where('slug', 'commercial')->firstOrFail();

        $owner = User::factory()->create([
            'role' => 'super_admin',
            'departement_id' => $communication->id,
            'password' => 'password',
        ]);
        $outsider = User::factory()->create([
            'role' => 'super_admin',
            'departement_id' => $commercial->id,
            'password' => 'password',
        ]);

        $test = new OnlineTest([
            'nom' => 'Admission communication',
            'description' => 'Questions confidentielles du service communication.',
            'duree_minutes' => 30,
            'actif' => true,
        ]);
        $test->departement_id = $communication->id;
        $test->save();

        OnlineTestQuestion::create([
            'online_test_id' => $test->id,
            'enonce' => 'Quelle est la capitale ?',
            'type' => 'qcm',
            'options' => ['Paris', 'Lyon'],
            'bonne_reponse' => 'Paris',
            'points' => 1,
            'position' => 1,
        ]);

        $invitation = OnlineTestInvitation::create([
            'online_test_id' => $test->id,
            'token' => OnlineTestInvitation::generateToken(),
            'candidat_nom' => 'Aina Rakoto',
            'candidat_email' => 'aina.rakoto@example.com',
            'statut' => OnlineTestInvitation::STATUT_EN_ATTENTE,
        ]);

        $this->actingAs($owner)
            ->withSession(['departement_actif_id' => $communication->id])
            ->get(route('crm.online-tests.links.index'))
            ->assertOk()
            ->assertSee('Aina Rakoto')
            ->assertSee('aina.rakoto@example.com')
            ->assertSee($invitation->token);

        $this->actingAs($outsider)
            ->withSession(['departement_actif_id' => $commercial->id])
            ->get(route('crm.online-tests.links.index'))
            ->assertOk()
            ->assertDontSee('Aina Rakoto')
            ->assertDontSee('aina.rakoto@example.com')
            ->assertDontSee($invitation->token);

        $this->actingAs($outsider)
            ->withSession(['departement_actif_id' => $commercial->id])
            ->get(route('crm.online-tests.results.show', $invitation))
            ->assertNotFound();

        $this->actingAs($outsider)
            ->withSession(['departement_actif_id' => $commercial->id])
            ->delete(route('crm.online-tests.links.destroy', $invitation))
            ->assertNotFound();

        $this->assertDatabaseHas('online_test_invitations', ['id' => $invitation->id]);
    }

    public function test_guest_can_still_open_the_candidate_link(): void
    {
        $communication = Departement::query()->where('slug', 'communication')->firstOrFail();

        $test = new OnlineTest([
            'nom' => 'Test public',
            'duree_minutes' => 20,
            'actif' => true,
        ]);
        $test->departement_id = $communication->id;
        $test->save();

        OnlineTestQuestion::create([
            'online_test_id' => $test->id,
            'enonce' => '2 + 2 ?',
            'type' => 'qcm',
            'options' => ['3', '4'],
            'bonne_reponse' => '4',
            'points' => 1,
            'position' => 1,
        ]);

        $invitation = OnlineTestInvitation::create([
            'online_test_id' => $test->id,
            'token' => OnlineTestInvitation::generateToken(),
            'candidat_nom' => 'Candidat Invite',
            'statut' => OnlineTestInvitation::STATUT_EN_ATTENTE,
        ]);

        $this->get(route('online-tests.take.show', $invitation->token))
            ->assertOk()
            ->assertSee('Test public')
            ->assertSee('Candidat Invite');
    }
}
