<?php

namespace Tests\Feature\Crm;

use App\Models\CrmActivity;
use App\Models\CrmCandidate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CandidateAbandonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');
        config(['app.url' => 'http://localhost']);
    }

    public function test_abandon_requires_a_reason_and_moves_candidate_out_of_active_pipeline(): void
    {
        $user = User::factory()->create([
            'role' => 'super_admin',
            'password' => 'password',
        ]);

        $candidate = CrmCandidate::create([
            'prenom' => 'Tiaray',
            'nom' => 'Mahandy',
            'programme' => 'B1',
            'statut' => 'intention_deposee',
            'advisor_id' => $user->id,
            'abandon' => false,
        ]);

        $this->actingAs($user)
            ->from(route('crm.candidats.show', $candidate))
            ->post(route('crm.candidats.abandon', $candidate), [])
            ->assertRedirect(route('crm.candidats.show', $candidate))
            ->assertSessionHasErrors('abandon_raison');

        $this->actingAs($user)
            ->post(route('crm.candidats.abandon', $candidate), [
                'abandon_raison' => 'Choix d’une autre école',
            ])
            ->assertRedirect(route('crm.candidats.show', $candidate));

        $candidate->refresh();
        $this->assertTrue($candidate->abandon);
        $this->assertSame('Choix d’une autre école', $candidate->abandon_raison);
        $this->assertSame('intention_deposee', $candidate->statut);

        $this->assertDatabaseHas('crm_activities', [
            'crm_candidate_id' => $candidate->id,
            'type' => CrmActivity::TYPE_ABANDON,
            'title' => 'Candidature abandonnée',
        ]);
    }
}
