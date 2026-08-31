<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Sous-dossier (déploiement OVH sur /communication)
|--------------------------------------------------------------------------
|
| L'application est servie depuis https://.../communication via une
| réécriture Apache. On indique à Laravel que le front controller se
| trouve dans "/communication" (sans toucher à REQUEST_URI) : le routeur
| voit alors "/login" tout en conservant "/communication" dans les URLs
| générées (liens, redirections, "intended"). En local (sans préfixe),
| ce bloc est ignoré.
|
*/

$basePath = '/communication';
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$trimmedPath = '/'.trim($requestPath, '/');

// /communication et /communication/ (casse indifférente) ne doivent pas passer
// par le routeur Laravel : en sous-dossier, GET / est souvent vu comme HEAD
// seul (405) alors que /login fonctionne. Les utilisateurs déjà connectés
// sont renvoyés vers l'accueil par le middleware guest de la page login.
if (strcasecmp($trimmedPath, $basePath) === 0) {
    $query = $_SERVER['QUERY_STRING'] ?? '';
    header('Location: '.$basePath.'/login'.($query !== '' ? '?'.$query : ''), true, 302);
    header('Cache-Control: no-store');
    exit;
}

if (strncasecmp($requestPath, $basePath.'/', strlen($basePath) + 1) === 0) {
    $_SERVER['SCRIPT_NAME'] = $basePath.'/index.php';
    $_SERVER['PHP_SELF'] = $basePath.'/index.php';
    $after = substr($requestPath, strlen($basePath));
    $_SERVER['PATH_INFO'] = ($after === '' || $after === false) ? '/' : $after;
}

/*
|--------------------------------------------------------------------------
| Check If The Application Is Under Maintenance
|--------------------------------------------------------------------------
|
| If the application is in maintenance / demo mode via the "down" command
| we will load this file so that any pre-rendered content can be shown
| instead of starting the framework, which could cause an exception.
|
*/

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| this application. We just need to utilize it! We'll simply require it
| into the script here so we don't need to manually load our classes.
|
*/

require __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request using
| the application's HTTP kernel. Then, we will send the response back
| to this client's browser, allowing them to enjoy our application.
|
*/

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
