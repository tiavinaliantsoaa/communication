<?php

namespace Tests\Unit\Crm;

use App\Models\CrmCandidate;
use App\Models\CrmCandidateDocument;
use App\Models\CrmDocumentType;
use App\Models\CrmProgramme;
use App\Models\User;
use App\Services\Crm\GlideImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlideImporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_does_not_insert_rows(): void
    {
        $user = $this->makeUser();
        $importer = app(GlideImporter::class);

        $report = $importer->import(
            base_path('tests/fixtures/glide/candidates.csv'),
            base_path('tests/fixtures/glide/documents.csv'),
            ['dry_run' => true, 'create_lookups' => true],
            $user
        );

        $this->assertTrue($report['dry_run']);
        $this->assertSame(3, $report['candidates_created']);
        $this->assertSame(2, $report['documents_created']);
        $this->assertSame(1, $report['documents_skipped_unmatched']);
        $this->assertSame(0, CrmCandidate::count());
        $this->assertSame(0, CrmCandidateDocument::count());
    }

    public function test_import_creates_candidates_documents_and_lookups(): void
    {
        $advisor = $this->makeUser(['name' => 'Alice Advisor']);
        $importer = app(GlideImporter::class);

        $report = $importer->import(
            base_path('tests/fixtures/glide/candidates.csv'),
            base_path('tests/fixtures/glide/documents.csv'),
            ['dry_run' => false, 'create_lookups' => true],
            $advisor
        );

        $this->assertSame(3, $report['candidates_created']);
        $this->assertSame(2, $report['documents_created']);
        $this->assertSame(1, $report['documents_skipped_unmatched']);
        $this->assertSame(3, CrmCandidate::count());

        $jean = CrmCandidate::where('glide_applicant_id', 'app-jean')->first();
        $this->assertNotNull($jean);
        $this->assertSame('Jean', $jean->prenom);
        $this->assertSame('facebook', $jean->source);
        $this->assertSame($advisor->id, $jean->advisor_id);
        $this->assertSame('decouverte', $jean->statut);
        $this->assertFalse($jean->abandon);
        $this->assertSame(2, $jean->documents()->count());

        $marie = CrmCandidate::where('glide_applicant_id', 'app-marie')->first();
        $this->assertTrue($marie->abandon);
        $this->assertSame('escm_tour', $marie->source);
        $this->assertSame('Tuléar', $marie->escm_tour_ville);

        $paul = CrmCandidate::where('glide_applicant_id', 'app-paul')->first();
        $this->assertSame('inscrit', $paul->statut);
        $this->assertTrue($paul->paiement_acompte);

        $this->assertTrue(CrmProgramme::where('label', 'Marketing')->exists());
        $this->assertTrue(CrmDocumentType::where('label', 'CV')->exists());

        $cv = $jean->documents()->whereHas('type', fn ($q) => $q->where('label', 'CV'))->first();
        $this->assertTrue($cv->isExternal());
        $this->assertStringContainsString('cv.pdf', $cv->url);
    }

    public function test_second_import_skips_existing_candidates(): void
    {
        $user = $this->makeUser();
        $importer = app(GlideImporter::class);
        $options = ['dry_run' => false, 'create_lookups' => true];

        $importer->import(base_path('tests/fixtures/glide/candidates.csv'), null, $options, $user);
        $report = $importer->import(base_path('tests/fixtures/glide/candidates.csv'), null, $options, $user);

        $this->assertSame(0, $report['candidates_created']);
        $this->assertSame(3, $report['candidates_skipped_existing']);
        $this->assertSame(3, CrmCandidate::count());
    }

    private function makeUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'super_admin',
            'password' => 'password',
        ], $overrides));
    }
}
