<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\EditorialEvent;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CalendrierEditorialValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');
        config(['app.url' => 'http://localhost']);
        Permission::syncCatalog();
    }

    public function test_an_editor_without_validate_permission_keeps_an_existing_approval(): void
    {
        $editor = $this->editor(['calendrier_editorial.update']);
        $event = $this->validatedEvent($editor);

        $this->actingAs($editor)
            ->put(route('calendrier-editorial.update', $event), $this->payload([
                'titre' => 'Post corrigé',
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $event->refresh();
        $this->assertSame('Post corrigé', $event->titre);
        $this->assertTrue($event->valide);
    }

    public function test_an_editor_without_validate_permission_cannot_mark_an_event_validated(): void
    {
        $editor = $this->editor(['calendrier_editorial.update']);
        $event = $this->validatedEvent($editor, false);

        $this->actingAs($editor)
            ->put(route('calendrier-editorial.update', $event), $this->payload([
                'valide' => '1',
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertFalse($event->refresh()->valide);
    }

    public function test_a_validator_can_clear_an_approval(): void
    {
        $editor = $this->editor([
            'calendrier_editorial.update',
            'calendrier_editorial.validate',
        ]);
        $event = $this->validatedEvent($editor);

        $this->actingAs($editor)
            ->put(route('calendrier-editorial.update', $event), $this->payload())
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertFalse($event->refresh()->valide);
    }

    public function test_updating_with_multiple_categories_duplicates_the_event(): void
    {
        $editor = $this->editor(['calendrier_editorial.update']);
        $event = $this->validatedEvent($editor, false);

        $this->actingAs($editor)
            ->put(route('calendrier-editorial.update', $event), $this->payload([
                'titre' => 'Post multi',
                'categorie' => ['facebook', 'linkedin', 'befiana_sms'],
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $event->refresh();
        $this->assertSame('Post multi', $event->titre);
        $this->assertSame(['facebook'], $event->categorie);

        $duplicates = EditorialEvent::query()
            ->where('titre', 'Post multi')
            ->where('id', '!=', $event->id)
            ->get();

        $this->assertCount(2, $duplicates);
        $this->assertEqualsCanonicalizing(
            [['linkedin'], ['befiana_sms']],
            $duplicates->pluck('categorie')->all()
        );
    }

    public function test_escm_and_saan_store_a_reel_and_reject_files_over_20_mo(): void
    {
        Storage::fake('public');
        $editor = $this->editor([
            'calendrier_editorial.create',
            'calendrier_editorial.view',
            'calendrier_saan.create',
        ]);

        $reel = UploadedFile::fake()->create('story.mp4', 500, 'video/mp4');

        $this->actingAs($editor)
            ->post(route('calendrier-editorial.store'), $this->payload([
                'titre' => 'Contenu reel',
                'reel' => $reel,
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $event = EditorialEvent::query()->where('titre', 'Contenu reel')->firstOrFail();
        $this->assertNotNull($event->reel_path);
        $this->assertSame('story.mp4', $event->reel_nom);
        Storage::disk('public')->assertExists($event->reel_path);

        $this->actingAs($editor)
            ->get(route('calendrier-editorial'))
            ->assertOk()
            ->assertSee('Reel', false);

        $tooLarge = UploadedFile::fake()->create('long.mp4', 20481, 'video/mp4');

        $this->actingAs($editor)
            ->post(route('calendrier-saan.store'), $this->payload([
                'titre' => 'Reel trop lourd',
                'reel' => $tooLarge,
            ]))
            ->assertSessionHasErrors('reel');

        $this->assertNull(EditorialEvent::query()->where('titre', 'Reel trop lourd')->first());
    }

    public function test_krismasy_ignores_a_reel_upload(): void
    {
        Storage::fake('public');
        $editor = $this->editor(['calendrier_krismasy.create']);

        $this->actingAs($editor)
            ->post(route('calendrier-krismasy.store'), $this->payload([
                'titre' => 'Sans reel',
                'reel' => UploadedFile::fake()->create('story.mp4', 200, 'video/mp4'),
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $event = EditorialEvent::query()->where('titre', 'Sans reel')->firstOrFail();
        $this->assertSame(EditorialEvent::SERVICE_KRISMASY, $event->service);
        $this->assertNull($event->reel_path);
    }

    /**
     * @param  list<string>  $keys
     */
    private function editor(array $keys): User
    {
        $communication = Departement::query()->where('slug', 'communication')->firstOrFail();
        $user = User::factory()->create([
            'role' => 'stagiaire',
            'departement_id' => $communication->id,
            'password' => 'password',
        ]);
        $user->permissions()->sync(
            Permission::query()->whereIn('key', $keys)->pluck('id')
        );

        return $user;
    }

    private function validatedEvent(User $owner, bool $valide = true): EditorialEvent
    {
        $this->actingAs($owner);

        return EditorialEvent::create([
            'service' => EditorialEvent::SERVICE_EDITORIAL,
            'titre' => 'Post validé',
            'categorie' => ['facebook'],
            'type_contenu' => 'FP',
            'date_debut' => '2026-10-20',
            'statut' => 'planifie',
            'valide' => $valide,
            'texte_publication' => 'Texte initial',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'titre' => 'Post validé',
            'categorie' => ['facebook'],
            'type_contenu' => 'FP',
            'date_debut' => '2026-10-20',
            'statut' => 'planifie',
            'texte_publication' => 'Texte initial',
        ], $overrides);
    }
}
