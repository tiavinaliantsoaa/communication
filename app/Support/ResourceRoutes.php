<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

class ResourceRoutes
{
    /**
     * @param  array{parameters?: array<string, string>}  $options
     */
    public static function register(string $uri, string $controller, string $permission, array $options = []): void
    {
        $parameters = $options['parameters'] ?? [];
        $map = [
            'view' => ['index'],
            'create' => ['create', 'store'],
            'update' => ['edit', 'update'],
            'delete' => ['destroy'],
        ];

        foreach ($map as $ability => $only) {
            Route::middleware('permission:'.$permission.'.'.$ability)->group(function () use ($uri, $controller, $only, $parameters) {
                $route = Route::resource($uri, $controller)->only($only);
                if ($parameters !== []) {
                    $route->parameters($parameters);
                }
            });
        }
    }
}
