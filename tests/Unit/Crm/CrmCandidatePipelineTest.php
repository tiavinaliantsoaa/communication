<?php

namespace Tests\Unit\Crm;

use App\Models\CrmCandidate;
use Tests\TestCase;

class CrmCandidatePipelineTest extends TestCase
{
    public function test_prospect_when_no_programme(): void
    {
        $this->assertSame('prospect', CrmCandidate::resolveStatutFromAttributes([]));
        $this->assertSame('prospect', CrmCandidate::resolveStatutFromAttributes([
            'programme' => '  ',
        ]));
    }

    public function test_intention_deposee_when_programme_is_chosen(): void
    {
        $this->assertSame('intention_deposee', CrmCandidate::resolveStatutFromAttributes([
            'programme' => 'B1',
        ]));
    }

    public function test_evaluation_when_test_is_completed(): void
    {
        $this->assertSame('evaluation', CrmCandidate::resolveStatutFromAttributes([
            'programme' => 'B1',
            'validation_test' => true,
        ]));
    }

    public function test_inscrit_when_deposit_or_full_payment(): void
    {
        $this->assertSame('inscrit', CrmCandidate::resolveStatutFromAttributes([
            'programme' => 'B1',
            'validation_test' => true,
            'paiement_acompte' => true,
        ]));
        $this->assertSame('inscrit', CrmCandidate::resolveStatutFromAttributes([
            'paiement_totalite' => true,
        ]));
    }

    public function test_legacy_flags_do_not_change_pipeline(): void
    {
        $this->assertSame('intention_deposee', CrmCandidate::resolveStatutFromAttributes([
            'programme' => 'B1',
            'paiement_frais_test' => true,
            'lettre_admission' => true,
        ]));
    }
}
