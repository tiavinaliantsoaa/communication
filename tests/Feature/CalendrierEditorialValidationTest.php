<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\EditorialEvent;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
