<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\Permission;
use App\Models\User;
use App\Services\AccessService;
use App\Support\NavbarMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class GestionEcoleMenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');
        config(['app.url' => 'http://localhost']);
    }

    public function test_school_section_is_visible_only_for_the_pedagogique_department(): void
    {
        $communication = Departement::query()->where('slug', 'communication')->firstOrFail();
        $pedagogique = Departement::query()->where('slug', 'pedagogique')->firstOrFail();
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'departement_id' => $communication->id,
            'password' => 'password',
        ]);

        foreach (NavbarMenu::schoolKeys() as $key) {
            $this->assertFalse(NavbarMenu::enabled($communication->id, $key));
            $this->assertTrue(NavbarMenu::enabled($pedagogique->id, $key));
        }

        DB::table('departement_menus')->whereIn('menu_key', NavbarMenu::schoolKeys())->delete();
        NavbarMenu::flush();

        $this->assertFalse(NavbarMenu::enabled($communication->id, 'ecole_etudiants'));
        $this->assertTrue(NavbarMenu::enabled($pedagogique->id, 'ecole_etudiants'));

        $this->actingAs($admin)
            ->withSession(['departement_actif_id' => $communication->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Gestion École')
            ->assertDontSee('Gestion Etudiants');

        $this->actingAs($admin)
            ->withSession(['departement_actif_id' => $communication->id])
            ->get(route('ecole.etudiants.index'))
            ->assertForbidden();

        $menu = $this->actingAs($admin)
            ->withSession(['departement_actif_id' => $pedagogique->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Gestion École')
            ->assertSee('Gestion Etudiants')
            ->assertSee('Gestion Classe')
            ->assertSee('Gestion Groupe')
            ->assertSee('Emplois du temps')
            ->assertSee('Retard et Absence')
            ->assertSee('Bulletin de note')
            ->assertSee('Paramètre école');

        $this->assertSame(1, substr_count($menu->getContent(), 'Gestion École'));

        foreach ([
            'ecole.dashboard' => 'Dashboard',
            'ecole.etudiants.index' => 'Aucun étudiant pour le moment.',
            'ecole.classes.index' => 'Aucune classe pour le moment.',
            'ecole.groupes.index' => 'Aucun groupe pour le moment.',
            'ecole.emplois.index' => 'Aucun emploi du temps pour le moment.',
            'ecole.absences.index' => 'Aucun retard ni absence pour le moment.',
            'ecole.bulletins.index' => 'Aucun bulletin pour le moment.',
        ] as $route => $text) {
            $this->actingAs($admin)
                ->withSession(['departement_actif_id' => $pedagogique->id])
                ->get(route($route))
                ->assertOk()
                ->assertSee($text);
        }
    }

    public function test_pedagogique_staff_receive_the_school_menu_and_other_departments_do_not(): void
    {
        $communication = Departement::query()->where('slug', 'communication')->firstOrFail();
        $pedagogique = Departement::query()->where('slug', 'pedagogique')->firstOrFail();

        $teacher = User::factory()->create([
            'role' => 'responsable_communication',
            'departement_id' => $pedagogique->id,
            'password' => 'password',
        ]);
        $commercial = User::factory()->create([
            'role' => 'responsable_communication',
            'departement_id' => $communication->id,
            'password' => 'password',
        ]);

        AccessService::grantPedagogiqueMenus($teacher);
        $commercial->permissions()->sync([
            Permission::query()->where('key', 'ecole.view')->value('id'),
        ]);

        $this->actingAs($teacher)
            ->get(route('ecole.classes.index'))
            ->assertOk()
            ->assertSee('Gestion École')
            ->assertSee('Bulletin de note')
            ->assertSee('Aucune classe pour le moment.');

        $this->actingAs($commercial)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertDontSee('Gestion École');

        $this->actingAs($commercial)
            ->get(route('ecole.classes.index'))
            ->assertForbidden();
    }

    public function test_a_new_department_does_not_receive_the_school_menus(): void
    {
        $alumni = Departement::create([
            'nom' => 'Alumni',
            'slug' => 'alumni',
            'is_system' => false,
        ]);
        NavbarMenu::enableAll($alumni);

        foreach (NavbarMenu::schoolKeys() as $key) {
            $this->assertFalse(NavbarMenu::enabled($alumni->id, $key));
        }
    }
}
