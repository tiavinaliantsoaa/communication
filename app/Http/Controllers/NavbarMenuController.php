<?php

namespace App\Http\Controllers;

use App\Models\Departement;
use App\Services\ActivityLogger;
use App\Support\NavbarMenu;
use Illuminate\Http\Request;

class NavbarMenuController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless($request->user()?->isSuperAdmin(), 403);

            return $next($request);
        });
    }

    public function index()
    {
        $departements = Departement::query()->with('menus')->orderBy('nom')->get();

        $enabled = [];
        foreach ($departements as $departement) {
            $stored = $departement->menus->pluck('enabled', 'menu_key');
            foreach (NavbarMenu::keys() as $key) {
                $enabled[$departement->id][$key] = (bool) ($stored[$key] ?? true);
            }
        }

        return view('navbar.index', [
            'title' => 'Gestion liste navbar',
            'subtitle' => 'Menus visibles par département',
            'departements' => $departements,
            'sections' => collect(NavbarMenu::items())->groupBy('group'),
            'enabled' => $enabled,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'departements' => ['required', 'array'],
            'departements.*' => ['integer', 'exists:departements,id'],
            'menus' => ['nullable', 'array'],
            'menus.*' => ['array'],
            'menus.*.*' => ['string'],
        ]);

        $departements = Departement::query()->whereIn('id', $data['departements'])->get();

        foreach ($departements as $departement) {
            NavbarMenu::sync($departement, $data['menus'][$departement->id] ?? []);
        }

        app(ActivityLogger::class)->log(
            'acces',
            $request->user()->name.' a mis à jour les menus de la navbar par département',
            $request->user(),
            'update',
            'Gestion liste navbar',
            route('navbar.index')
        );

        return redirect()->route('navbar.index')->with('success', 'Menus de la navbar enregistrés.');
    }
}
