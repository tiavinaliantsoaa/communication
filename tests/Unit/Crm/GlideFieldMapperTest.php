<?php

namespace Tests\Unit\Crm;

use App\Services\Crm\GlideCsv;
use App\Services\Crm\GlideFieldMapper;
use Tests\TestCase;

class GlideFieldMapperTest extends TestCase
{
    public function test_it_maps_a_glide_candidate_row(): void
    {
        $rows = GlideCsv::rows(base_path('tests/fixtures/glide/candidates.csv'));
        $this->assertCount(3, $rows);

        $mapper = new GlideFieldMapper;
        $jean = $mapper->mapCandidate($rows[0], ['alice advisor' => 9]);

        $this->assertSame('app-jean', $jean['glide_applicant_id']);
        $this->assertSame('Jean', $jean['prenom']);
        $this->assertSame('Rakoto', $jean['nom']);
        $this->assertSame('homme', $jean['genre']);
        $this->assertSame('2002-03-21', $jean['date_naissance']);
        $this->assertSame('jean@test.com', $jean['email']);
        $this->assertSame('Marketing', $jean['programme']);
        $this->assertSame('Fall 2025', $jean['annee_academique']);
        $this->assertSame('facebook', $jean['source']);
        $this->assertSame('https://facebook.com/jean', $jean['facebook_profil_url']);
        $this->assertSame(9, $jean['advisor_id']);
        $this->assertFalse($jean['abandon']);
        $this->assertSame('+261340000001', $jean['contact_parent_1']);
        $this->assertSame('+261340000002', $jean['contact_parent_2']);
        $this->assertSame('intention_deposee', $mapper->resolveStatut($jean));
    }

    public function test_it_maps_abandon_and_escm_tour(): void
    {
        $rows = GlideCsv::rows(base_path('tests/fixtures/glide/candidates.csv'));
        $marie = (new GlideFieldMapper)->mapCandidate($rows[1]);

        $this->assertTrue($marie['abandon']);
        $this->assertSame('Choix autre école', $marie['abandon_raison']);
        $this->assertSame('escm_tour', $marie['source']);
        $this->assertSame('Tuléar', $marie['escm_tour_ville']);
    }

    public function test_it_maps_inscrit_pipeline_flags(): void
    {
        $rows = GlideCsv::rows(base_path('tests/fixtures/glide/candidates.csv'));
        $mapper = new GlideFieldMapper;
        $paul = $mapper->mapCandidate($rows[2]);

        $this->assertTrue($paul['paiement_frais_test']);
        $this->assertTrue($paul['validation_test']);
        $this->assertTrue($paul['lettre_admission']);
        $this->assertTrue($paul['paiement_acompte']);
        $this->assertFalse($paul['paiement_totalite']);
        $this->assertSame('site_web', $paul['source']);
        $this->assertSame('inscrit', $mapper->resolveStatut($paul));
        $this->assertStringContainsString('ID ESCM : E24-001', $paul['notes']);
        $this->assertStringContainsString('Évaluation : Favorable', $paul['notes']);
    }

    public function test_it_maps_a_document_row(): void
    {
        $rows = GlideCsv::rows(base_path('tests/fixtures/glide/documents.csv'));
        $doc = (new GlideFieldMapper)->mapDocument($rows[0]);

        $this->assertSame('app-jean', $doc['glide_applicant_id']);
        $this->assertSame('CV', $doc['type_label']);
        $this->assertStringContainsString('cv.pdf', $doc['external_url']);
        $this->assertSame('application/pdf', $doc['mime']);
    }

    public function test_year_filter_keeps_2025_and_skips_2024(): void
    {
        $rows = GlideCsv::rows(base_path('tests/fixtures/glide/candidates.csv'));
        $mapper = new GlideFieldMapper;

        $this->assertTrue($mapper->matchesYearFilter($rows[0], '2025-2026'));
        $this->assertFalse($mapper->matchesYearFilter($rows[1], '2025-2026'));
        $this->assertTrue($mapper->matchesYearFilter($rows[2], '2025-2026'));
        $this->assertTrue($mapper->matchesYearFilter($rows[1], 'all'));
    }

    public function test_year_filter_ignores_created_at_alone(): void
    {
        $rows = GlideCsv::rows(base_path('tests/fixtures/glide/candidates.csv'));
        $marie = $rows[1];
        $marie['created_at'] = '10/03/2025';
        $marie['created_at year'] = '2025';

        $this->assertFalse((new GlideFieldMapper)->matchesYearFilter($marie, '2025-2026'));
    }
}
