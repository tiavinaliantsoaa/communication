<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictSchoolAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->isCompteEcole()) {
            return $next($request);
        }

        $route = $request->route()?->getName();
        $autorisees = [
            'ecole.dashboard',
            'ecole.emplois.index',
            'profile.edit',
            'profile.update',
            'profile.destroy',
        ];

        if ($user->isParent() || $user->isEtudiant()) {
            $autorisees[] = 'ecole.absences.index';
            $autorisees[] = 'ecole.etudiants.show';
        }

        if (! in_array($route, $autorisees, true)) {
            abort(403, 'Cette page n’est pas disponible pour ce compte.');
        }

        return $next($request);
    }
}
