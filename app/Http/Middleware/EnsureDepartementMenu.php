<?php

namespace App\Http\Middleware;

use App\Support\NavbarMenu;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDepartementMenu
{
    public function handle(Request $request, Closure $next, ?string $menuKey = null): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $allowed = $menuKey
            ? NavbarMenu::enabled($user->currentDepartementId(), $menuKey)
            : NavbarMenu::routeAllowed($user->currentDepartementId(), $request->route()?->getName());

        if (! $allowed) {
            abort(403, 'Cette section n’est pas disponible pour ce département.');
        }

        return $next($request);
    }
}
