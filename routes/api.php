<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\ProjetApiController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::get('/dashboard', DashboardApiController::class)
        ->middleware(['permission:dashboard.view', 'departement.menu:dashboard']);

    Route::prefix('projet')->middleware('departement.menu:gestion_projet')->group(function () {
        Route::get('/board', [ProjetApiController::class, 'board'])
            ->middleware('permission:gestion_projet.view');
        Route::get('/cartes/{projet}', [ProjetApiController::class, 'show'])
            ->middleware('permission:gestion_projet.view');
        Route::post('/cartes', [ProjetApiController::class, 'store'])
            ->middleware('permission:gestion_projet.create');
        Route::post('/cartes/{projet}/commentaires', [ProjetApiController::class, 'storeCommentaire'])
            ->middleware('permission:gestion_projet.create');
        Route::patch('/cartes/{projet}', [ProjetApiController::class, 'update'])
            ->middleware('permission:gestion_projet.update');
        Route::post('/move', [ProjetApiController::class, 'move'])
            ->middleware('permission:gestion_projet.update');
    });
});
