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

class OnlineTestTakeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');
        config(['app.url' => 'http://localhost']);
    }

    public function test_candidate_must_accept_rules_and_fullscreen_to_start(): void
    {
        $invitation = $this->makeInvitation();

        $this->post(route('online-tests.take.start', $invitation->token), [])
            ->assertSessionHasErrors(['accepte_regles', 'fullscreen_ok']);

        $this->postJson(route('online-tests.take.start', $invitation->token), [
            'accepte_regles' => '1',
            'fullscreen_ok' => '1',
        ])->assertOk()
            ->assertJsonStructure(['ok', 'remaining', 'ends_at']);

        $invitation->refresh();
        $this->assertSame(OnlineTestInvitation::STATUT_EN_COURS, $invitation->statut);
        $this->assertNotNull($invitation->started_at);
        $this->assertNotNull($invitation->ends_at);
        $this->assertTrue($invitation->ends_at->greaterThan($invitation->started_at));
    }

    public function test_suspicious_action_cancels_the_attempt(): void
    {
        $invitation = $this->makeInvitation();
        $invitation->update([
            'statut' => OnlineTestInvitation::STATUT_EN_COURS,
            'started_at' => now(),
            'ends_at' => now()->addMinutes(30),
        ]);

        $this->postJson(route('online-tests.take.cancel', $invitation->token), [
            'reason' => 'tab_change',
        ])->assertOk();

        $invitation->refresh();
        $this->assertSame(OnlineTestInvitation::STATUT_ANNULE, $invitation->statut);
        $this->assertNotNull($invitation->cancelled_at);
        $this->assertStringContainsString('onglet', strtolower($invitation->cancel_reason));
    }

    public function test_timer_is_server_side_and_cannot_be_reset_by_client(): void
    {
        $invitation = $this->makeInvitation();
        $started = now()->subMinutes(10);
        $ends = $started->copy()->addMinutes(45);
        $invitation->update([
            'statut' => OnlineTestInvitation::STATUT_EN_COURS,
            'started_at' => $started,
            'ends_at' => $ends,
        ]);

        $response = $this->getJson(route('online-tests.take.time', $invitation->token));
        $response->assertOk();
        $remaining = $response->json('remaining');

        $this->assertGreaterThan(30 * 60, $remaining);
        $this->assertLessThanOrEqual(35 * 60, $remaining);
    }

    private function makeInvitation(): OnlineTestInvitation
    {
        $communication = Departement::query()->where('slug', 'communication')->firstOrFail();
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'departement_id' => $communication->id,
            'password' => 'password',
        ]);

        $this->actingAs($admin);
        $test = OnlineTest::create([
            'nom' => 'Test logique',
            'duree_minutes' => 45,
            'actif' => true,
        ]);
        OnlineTestQuestion::create([
            'online_test_id' => $test->id,
            'enonce' => '2+2 ?',
            'type' => 'qcm',
            'options' => ['3', '4', '5'],
            'bonne_reponse' => '4',
            'points' => 2,
            'position' => 1,
        ]);

        auth()->logout();

        return OnlineTestInvitation::create([
            'online_test_id' => $test->id,
            'token' => OnlineTestInvitation::generateToken(),
            'candidat_nom' => 'Jean Test',
            'statut' => OnlineTestInvitation::STATUT_EN_ATTENTE,
        ]);
    }
}
