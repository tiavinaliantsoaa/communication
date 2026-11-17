<?php

namespace Tests\Unit\Crm;

use App\Models\CrmCandidate;
use App\Models\CrmCandidateDocument;
use App\Models\CrmDocumentType;
use App\Models\User;
use App\Services\Crm\CrmCandidateDocumentImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CrmCandidateDocumentImporterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Http::fake(function () {
            return Http::response("%PDF-1.4\nfake", 200, ['Content-Type' => 'application/pdf']);
        });
    }

    public function test_import_downloads_the_file_and_attaches_it_to_the_application_id(): void
    {
        $user = $this->makeUser();
        $candidate = $this->makeCandidate($user);
        $path = $this->csv([
            'Row ID,Type,URL,Applicants Unique/ID,Uploaded_at',
            'doc-cv,CV,https://storage.googleapis.com/glide-prod.appspot.com/uploads-v2/demo/pub/cv.pdf,'.$candidate->id.',19/03/2024 10:00:09',
            'doc-missing,CV,https://storage.googleapis.com/glide-prod.appspot.com/uploads-v2/demo/pub/missing.pdf,999999,19/03/2024 10:01:00',
            'doc-bad,CV,pas-une-url,'.$candidate->id.',19/03/2024 10:02:00',
        ]);

        $report = app(CrmCandidateDocumentImporter::class)->import($path, $user);

        $this->assertSame(1, $report['documents_created']);
        $this->assertSame(1, $report['documents_skipped_unmatched']);
        $this->assertSame(1, $report['documents_skipped_invalid']);
        $this->assertSame(1, $report['document_types_created']);

        $document = CrmCandidateDocument::first();
        $this->assertSame($candidate->id, $document->crm_candidate_id);
        $this->assertSame('doc-cv', $document->glide_document_id);
        $this->assertNull($document->external_url);
        $this->assertTrue(Storage::disk('local')->exists($document->path));
        $this->assertFalse($document->isExternal());
        $this->assertSame('CV', CrmDocumentType::first()->label);
        Http::assertSentCount(1);
    }

    public function test_reimport_does_not_download_a_document_already_stored(): void
    {
        $user = $this->makeUser();
        $candidate = $this->makeCandidate($user);
        $path = $this->csv([
            'ID,Type,URL,Row ID',
            $candidate->id.',Passeport,https://storage.googleapis.com/glide-prod.appspot.com/uploads-v2/demo/pub/passeport.pdf,doc-pass',
        ]);

        $importer = app(CrmCandidateDocumentImporter::class);
        $first = $importer->import($path, $user);
        $second = $importer->import($path, $user);

        $this->assertSame(1, $first['documents_created']);
        $this->assertSame(1, $second['documents_already_local']);
        $this->assertSame(1, CrmCandidateDocument::count());
        Http::assertSentCount(1);
    }

    public function test_settings_page_shows_the_document_import_section_after_glide(): void
    {
        $user = $this->makeUser();

        $html = $this->actingAs($user)
            ->get(route('crm.settings'))
            ->assertOk()
            ->assertSee('Importer des documents')
            ->assertSee('CSV Documents')
            ->assertSee('IMPORTER')
            ->getContent();

        $glide = strpos($html, 'Import depuis Glide');
        $documents = strpos($html, 'Importer des documents');
        $this->assertNotFalse($glide);
        $this->assertNotFalse($documents);
        $this->assertGreaterThan($glide, $documents);
    }

    public function test_settings_import_accepts_the_uploaded_csv(): void
    {
        $user = $this->makeUser();
        $candidate = $this->makeCandidate($user);
        $file = UploadedFile::fake()->createWithContent('documents.csv', implode("\n", [
            'Row ID;Type;URL;Applicants Unique/ID;Uploaded_at',
            'doc-cv;CV;https://storage.googleapis.com/glide-prod.appspot.com/uploads-v2/demo/pub/cv.pdf;'.$candidate->id.';19/03/2024 10:00:09',
        ]));

        $this->actingAs($user)
            ->post(route('crm.settings.documents.import'), [
                'documents_file' => $file,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(1, $candidate->documents()->count());
        $this->assertFalse($candidate->documents()->first()->isExternal());
    }

    private function makeUser(): User
    {
        return User::factory()->create([
            'role' => 'super_admin',
            'password' => 'password',
        ]);
    }

    private function makeCandidate(User $user): CrmCandidate
    {
        return CrmCandidate::create([
            'prenom' => 'Jean',
            'nom' => 'Rakoto',
            'telephone' => '0341111111',
            'email' => 'jean@test.com',
            'programme' => 'B1',
            'annee_academique' => 'Fall 2026',
            'statut' => 'intention_deposee',
            'advisor_id' => $user->id,
            'abandon' => false,
        ]);
    }

    /**
     * @param  list<string>  $lines
     */
    private function csv(array $lines): string
    {
        $path = tempnam(sys_get_temp_dir(), 'docs-csv');
        file_put_contents($path, implode("\n", $lines));

        return $path;
    }
}
