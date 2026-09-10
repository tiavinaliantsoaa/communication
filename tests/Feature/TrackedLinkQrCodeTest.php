<?php

namespace Tests\Feature;

use App\Models\TrackedLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class TrackedLinkQrCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');
        config(['app.url' => 'http://localhost']);
    }

    public function test_qr_image_is_available_on_the_show_page(): void
    {
        [$user, $link] = $this->makeLink();

        $this->actingAs($user)
            ->get(route('suivi-liens.show', $link))
            ->assertOk()
            ->assertSee('Voir QR code', false)
            ->assertSee('Télécharger QR code', false)
            ->assertSee('Image PNG', false)
            ->assertSee('Document PDF', false);
    }

    public function test_qr_png_endpoint_returns_a_png(): void
    {
        [$user, $link] = $this->makeLink();

        $response = $this->actingAs($user)->get(route('suivi-liens.qr', $link));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
        $this->assertSame("\x89PNG", substr($response->getContent(), 0, 4));
    }

    public function test_qr_can_be_downloaded_as_png_or_pdf(): void
    {
        [$user, $link] = $this->makeLink();

        $png = $this->actingAs($user)->get(route('suivi-liens.qr.download', [
            'suivi_lien' => $link,
            'format' => 'png',
        ]));
        $png->assertOk();
        $png->assertHeader('Content-Type', 'image/png');
        $this->assertStringContainsString('attachment', (string) $png->headers->get('Content-Disposition'));
        $this->assertSame("\x89PNG", substr($png->getContent(), 0, 4));

        $pdf = $this->actingAs($user)->get(route('suivi-liens.qr.download', [
            'suivi_lien' => $link,
            'format' => 'pdf',
        ]));
        $pdf->assertOk();
        $pdf->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
        $this->assertStringContainsString($link->short_url, $pdf->getContent());
    }

    /**
     * @return array{0: User, 1: TrackedLink}
     */
    private function makeLink(): array
    {
        $user = User::factory()->create([
            'role' => 'super_admin',
            'password' => 'password',
        ]);

        $link = TrackedLink::create([
            'user_id' => $user->id,
            'nom' => 'Inscription A4',
            'destination_url' => 'https://www.escm.mg/inscription',
            'slug' => 'a9fgyqzj',
            'actif' => true,
        ]);

        return [$user, $link];
    }
}
