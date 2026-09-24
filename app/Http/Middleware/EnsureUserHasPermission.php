<?php

namespace App\Http\Middleware;

use App\Services\AccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    /**
     * @param  string  ...$permissions  Any of these permissions grants access.
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if (! $user->permissions()->exists()) {
            AccessService::bootstrap();
            $user->unsetRelation('permissions');
        }

        if ($permissions === [] || $user->canAccessAny($permissions)) {
            return $next($request);
        }

        abort(403, 'Accès non autorisé.');
    }
}
