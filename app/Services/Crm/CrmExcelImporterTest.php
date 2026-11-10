<?php

namespace Tests\Unit\Crm;

use App\Models\CrmCandidate;
use App\Models\CrmCandidateDocument;
use App\Models\CrmDocumentType;
use App\Models\CrmProgramme;
use App\Models\User;
use App\Services\Crm\CrmExcelImporter;
use App\Services\Crm\SimpleXlsxReader;
use App\Services\Crm\SimpleXlsxWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CrmExcelImporterTest extends TestCase
{
    use RefreshDatabase;

    private string $candidatesPath;

    private string $documentsPath;

    protected function setUp(): void
    {
        parent::setUp();

        $directory = storage_path('framework/testing/crm-excel');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $this->candidatesPath = $directory.'/candidates.xlsx';
        $this->documentsPath = $directory.'/documents.xlsx';
        file_put_contents($this->candidatesPath, (new SimpleXlsxWriter)->build([
            'Row ID',
            'Unique Applicants ID',
            'Info/Civility',
            'Info/First Name',
            'Info/Last Name',
            'Info/ Date of Birth',
            'Info/ Email',
            'Info/Phone Number',
            'Wish/ Filiere',
            'Wish/ Academic Years',
            'Info/Page facebook',
            'Téléphone des parents',
            'Created_at',
            'Crée par',
            'Current Situation/ Level',
            'Localisation/ Address',
            'Localisation/ City',
            'Info/ ID ESCM',
            'Info/ Place of Birth',
            'Current Situation/ CV',
            'Abandon/ Reason ID',
            'Sources/ ID',
        ], [
            [
                'app-jean', 'app-jean', 'Monsieur', 'Jean', 'Rakoto', '25569', 'jean@test.com', '+261340000001',
                'Marketing', 'Fall 2025', 'https://facebook.com/jean', '+261340000010 | +261340000011',
                '25569.5', 'Alice Advisor', 'Terminale', 'Lot 1', 'Tana', 'E24-001', 'Antananarivo',
                'https://files.test/jean-cv.pdf', 'o9VJpDDpRZu53ppMr3-Nyg', 'VEjWqbtiRKC7tDRIyDDOkA',
            ],
            [
                'app-paul', 'app-paul', 'Antananarivo', 'Paul', 'Rabe', '25569', 'paul@test.com', '+261340000002',
                'Communication', 'Fall 2025', '', '', '25569', 'Personne Inconnue', 'L1 / B1', '', '', '', '',
                '', '', '',
            ],
            [
                'app-vide', 'app-vide', '', 't0pHHKXISqKhdOzYb1kUpA', '', '', '', '',
                '', '', '', '', '', '', '', '', '', '', '', '', '', '',
            ],
        ]));
        file_put_contents($this->documentsPath, (new SimpleXlsxWriter)->build([
            'Row ID', 'Type', 'URL', 'Applicants Unique/ID', 'Name',
        ], [
            ['doc-photo', "Photo d'identité", 'https://files.test/jean-photo.pdf', 'app-jean', 'photo.pdf'],
            ['doc-perdu', 'Passeport', 'https://files.test/perdu.pdf', 'inconnu', 'perdu.pdf'],
            ['doc-invalide', 'CV', 'pas-une-url', 'app-jean', ''],
        ], 'Documents'));
    }

    public function test_it_reads_normalized_rows_from_xlsx(): void
    {
        $rows = (new SimpleXlsxReader)->rows($this->candidatesPath);

        $this->assertCount(3, $rows);
        $this->assertSame('Jean', $rows[0]['info/first name']);
        $this->assertSame('25569', $rows[0]['info/ date of birth']);
        $this->assertSame('app-jean', $rows[0]['row id']);
    }

    public function test_import_creates_candidates_and_stores_downloaded_files(): void
    {
        Storage::fake('local');
        Http::fake([
            'https://files.test/*' => Http::response('%PDF-1.4 fiche', 200, ['Content-Type' => 'application/pdf']),
        ]);

        $user = $this->makeUser();
        $report = app(CrmExcelImporter::class)->import(
            $this->candidatesPath,
            $this->documentsPath,
            ['download' => true, 'profile_files' => true],
            $user
        );

        $this->assertSame(2, $report['candidates_created']);
        $this->assertSame(1, $report['candidates_skipped_invalid']);
        $this->assertSame(1, $report['documents_skipped_unmatched']);
        $this->assertSame(1, $report['documents_skipped_invalid']);
        $this->assertSame(2, $report['documents_downloaded']);
        $this->assertSame(0, $report['documents_download_failed']);
        $this->assertSame(2, CrmCandidate::count());

        $jean = CrmCandidate::where('glide_applicant_id', 'app-jean')->first();
        $this->assertNotNull($jean);
        $this->assertSame('Jean', $jean->prenom);
        $this->assertSame('homme', $jean->genre);
        $this->assertSame('1970-01-01', $jean->date_naissance->format('Y-m-d'));
        $this->assertSame('1970-01-01 12:00:00', $jean->created_at->format('Y-m-d H:i:s'));
        $this->assertSame('facebook', $jean->source);
        $this->assertSame('https://facebook.com/jean', $jean->facebook_profil_url);
        $this->assertSame('Marketing', $jean->programme);
        $this->assertSame('Fall 2025', $jean->annee_academique);
        $this->assertSame('intention_deposee', $jean->statut);
        $this->assertFalse($jean->abandon);
        $this->assertSame($user->id, $jean->advisor_id);
        $this->assertSame('+261340000010', $jean->contact_parent_1);
        $this->assertStringContainsString('ID ESCM : E24-001', $jean->notes);
        $this->assertStringContainsString('Lieu de naissance : Antananarivo', $jean->notes);

        $paul = CrmCandidate::where('glide_applicant_id', 'app-paul')->first();
        $this->assertNull($paul->genre);

        $this->assertTrue(CrmProgramme::where('label', 'Marketing')->exists());
        $this->assertTrue(CrmDocumentType::where('label', "Photo d'identité")->exists());
        $this->assertTrue(CrmDocumentType::where('label', 'CV')->exists());

        $photo = $jean->documents()->where('glide_document_id', 'doc-photo')->first();
        $this->assertNotNull($photo);
        $this->assertFalse($photo->isExternal());
        $this->assertSame('application/pdf', $photo->mime);
        $this->assertSame('photo.pdf', $photo->original_name);
        Storage::disk('local')->assertExists($photo->path);

        $cv = $jean->documents()->where('original_name', 'CV.pdf')->first();
        $this->assertNotNull($cv);
        Storage::disk('local')->assertExists($cv->path);
        Http::assertSentCount(2);
    }

    public function test_dry_run_does_not_write_or_download(): void
    {
        Storage::fake('local');
        Http::fake();

        $report = app(CrmExcelImporter::class)->import(
            $this->candidatesPath,
            $this->documentsPath,
            ['dry_run' => true],
            $this->makeUser()
        );

        $this->assertTrue($report['dry_run']);
        $this->assertSame(2, $report['candidates_created']);
        $this->assertSame(1, $report['documents_downloaded']);
        $this->assertSame(0, CrmCandidate::count());
        $this->assertSame(0, CrmCandidateDocument::count());
        Http::assertNothingSent();
    }

    public function test_second_run_skips_existing_candidate_and_file(): void
    {
        Storage::fake('local');
        Http::fake([
            'https://files.test/*' => Http::response('%PDF-1.4 fiche', 200, ['Content-Type' => 'application/pdf']),
        ]);

        $importer = app(CrmExcelImporter::class);
        $user = $this->makeUser();
        $importer->import($this->candidatesPath, $this->documentsPath, [], $user);
        $report = $importer->import($this->candidatesPath, $this->documentsPath, [], $user);

        $this->assertSame(0, $report['candidates_created']);
        $this->assertSame(2, $report['candidates_skipped_existing']);
        $this->assertSame(1, $report['documents_skipped_existing']);
        $this->assertSame(0, $report['documents_downloaded']);
        $this->assertSame(2, CrmCandidate::count());
        Http::assertSentCount(1);
    }

    public function test_update_changes_phone_without_clearing_existing_notes(): void
    {
        Storage::fake('local');
        Http::fake([
            'https://files.test/*' => Http::response('%PDF-1.4 fiche', 200, ['Content-Type' => 'application/pdf']),
        ]);

        $importer = app(CrmExcelImporter::class);
        $user = $this->makeUser();
        $importer->import($this->candidatesPath, $this->documentsPath, ['profile_files' => false], $user);

        file_put_contents($this->candidatesPath, (new SimpleXlsxWriter)->build([
            'Row ID', 'Unique Applicants ID', 'Info/Civility', 'Info/First Name', 'Info/Last Name',
            'Info/Phone Number', 'Info/ ID ESCM',
        ], [
            ['app-jean', 'app-jean', 'Monsieur', 'Jean', 'Rakoto', '+261349999999', 'E24-009'],
        ]));

        $report = $importer->import($this->candidatesPath, $this->documentsPath, [
            'update_existing' => true,
            'profile_files' => false,
        ], $user);

        $jean = CrmCandidate::where('glide_applicant_id', 'app-jean')->first();
        $this->assertSame(1, $report['candidates_updated']);
        $this->assertSame('+261349999999', $jean->telephone);
        $this->assertStringContainsString('ID ESCM : E24-001', $jean->notes);
        $this->assertFalse($jean->abandon);
    }

    public function test_download_limit_defers_remaining_files(): void
    {
        Storage::fake('local');
        Http::fake([
            'https://files.test/*' => Http::response('%PDF-1.4 fiche', 200, ['Content-Type' => 'application/pdf']),
        ]);

        $report = app(CrmExcelImporter::class)->import(
            $this->candidatesPath,
            $this->documentsPath,
            ['download_limit' => 1, 'profile_files' => true],
            $this->makeUser()
        );

        $this->assertSame(1, $report['documents_downloaded']);
        $this->assertSame(1, $report['documents_deferred']);
        Http::assertSentCount(1);
    }

    public function test_failed_download_keeps_the_link(): void
    {
        Storage::fake('local');
        Http::fake([
            'https://files.test/*' => Http::response('nope', 404),
        ]);

        $report = app(CrmExcelImporter::class)->import(
            $this->candidatesPath,
            $this->documentsPath,
            ['profile_files' => false],
            $this->makeUser()
        );

        $this->assertSame(1, $report['documents_download_failed']);
        $document = CrmCandidateDocument::first();
        $this->assertTrue($document->isExternal());
        $this->assertSame('https://files.test/jean-photo.pdf', $document->external_url);
    }

    public function test_command_dry_run_uses_the_given_workbooks(): void
    {
        Storage::fake('local');
        Http::fake();
        $this->makeUser();

        $this->artisan('crm:import-excel', [
            '--candidates' => $this->candidatesPath,
            '--documents' => $this->documentsPath,
            '--dry-run' => true,
            '--user' => 'alice.advisor@test.com',
        ])->assertSuccessful();

        $this->assertSame(0, CrmCandidate::count());
    }

    public function test_real_workbooks_are_readable_when_present(): void
    {
        $candidates = storage_path('app/imports/crm/crm_candidates.xlsx');
        $documents = storage_path('app/imports/crm/crm_candidates_documents.xlsx');
        if (! is_file($candidates) || ! is_file($documents)) {
            $this->markTestSkipped('Classeurs CRM absents de storage/app/imports/crm.');
        }

        Storage::fake('local');
        Http::fake();

        $report = app(CrmExcelImporter::class)->import($candidates, $documents, [
            'dry_run' => true,
            'profile_files' => false,
        ], $this->makeUser());

        $this->assertGreaterThan(2000, $report['candidates_created']);
        $this->assertGreaterThan(0, $report['documents_downloaded']);
        $this->assertLessThan(100, $report['documents_downloaded']);
        $this->assertSame(0, $report['documents_skipped_unmatched']);
        $this->assertSame(0, CrmCandidate::count());
    }

    private function makeUser(): User
    {
        return User::factory()->create([
            'name' => 'Alice Advisor',
            'email' => 'alice.advisor@test.com',
            'role' => 'super_admin',
            'password' => 'password',
        ]);
    }
}
