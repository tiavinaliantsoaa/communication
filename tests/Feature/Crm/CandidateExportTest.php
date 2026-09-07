<?php

namespace Tests\Feature\Crm;

use App\Models\CrmCandidate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;
use ZipArchive;

class CandidateExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');
        config(['app.url' => 'http://localhost']);
    }

    public function test_export_page_is_available(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->get(route('crm.export'))
            ->assertOk()
            ->assertSee('Colonnes du fichier Excel')
            ->assertSee('Année / Intake')
            ->assertSee('Exporter en Excel');
    }

    public function test_export_requires_at_least_one_column(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->from(route('crm.export'))
            ->post(route('crm.export.download'), [
                'population' => 'actifs',
            ])
            ->assertRedirect(route('crm.export'))
            ->assertSessionHasErrors('columns');
    }

    public function test_export_xlsx_contains_filtered_candidates_and_selected_columns(): void
    {
        $user = $this->makeUser();

        CrmCandidate::create([
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
        CrmCandidate::create([
            'prenom' => 'Marie',
            'nom' => 'Rabe',
            'telephone' => '0342222222',
            'email' => 'marie@test.com',
            'programme' => 'MBA1',
            'annee_academique' => 'Spring 2027',
            'statut' => 'evaluation',
            'advisor_id' => $user->id,
            'abandon' => false,
        ]);

        $response = $this->actingAs($user)
            ->post(route('crm.export.download'), [
                'population' => 'actifs',
                'columns' => ['full_name', 'telephone'],
                'programme' => ['B1'],
                'intake' => ['Fall 2026'],
            ]);

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $sheet = $this->sheetXml($response->streamedContent());
        $this->assertStringContainsString('Nom complet', $sheet);
        $this->assertStringContainsString('Téléphone', $sheet);
        $this->assertStringContainsString('Jean Rakoto', $sheet);
        $this->assertStringContainsString('0341111111', $sheet);
        $this->assertStringNotContainsString('Marie Rabe', $sheet);
        $this->assertStringNotContainsString('jean@test.com', $sheet);
    }

    private function makeUser(): User
    {
        return User::factory()->create([
            'role' => 'super_admin',
            'password' => 'password',
        ]);
    }

    private function sheetXml(string $binary): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx-test');
        file_put_contents($tmp, $binary);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($tmp) === true);
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $shared = $zip->getFromName('xl/sharedStrings.xml');
        $zip->close();
        @unlink($tmp);

        $this->assertNotFalse($xml);
        $this->assertNotFalse($shared);

        return $xml."\n".$shared;
    }
}
