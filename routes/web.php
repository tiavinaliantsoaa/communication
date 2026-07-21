<?php

use App\Http\Controllers\BudgetAnnuelController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CalendrierEditorialController;
use App\Http\Controllers\CampagneController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepenseController;
use App\Http\Controllers\EvenementController;
use App\Http\Controllers\FournisseurController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjetController;
use App\Http\Controllers\StatistiqueController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockMouvementController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('budget-annuels', BudgetAnnuelController::class)->except(['show']);
    Route::resource('budgets', BudgetController::class)->except(['show']);
    Route::resource('depenses', DepenseController::class)->except(['show']);
    Route::resource('fournisseurs', FournisseurController::class)->except(['show']);
    Route::resource('campagnes', CampagneController::class)->except(['show']);
    Route::prefix('stocks')->name('stocks.')->group(function () {
        Route::resource('mouvements', StockMouvementController::class)
            ->parameters(['mouvements' => 'mouvement'])
            ->except(['show']);
    });
    Route::resource('stocks', StockController::class)->except(['show']);

    Route::prefix('gestion-projet')->name('gestion-projet.')->group(function () {
        Route::get('/', [ProjetController::class, 'index'])->name('index');
        Route::post('/listes', [ProjetController::class, 'storeListe'])->name('listes.store');
        Route::patch('/listes/{liste}', [ProjetController::class, 'updateListe'])->name('listes.update');
        Route::delete('/listes/{liste}', [ProjetController::class, 'destroyListe'])->name('listes.destroy');
        Route::post('/background', [ProjetController::class, 'updateBackground'])->name('background');
        Route::post('/cartes', [ProjetController::class, 'store'])->name('cartes.store');
        Route::get('/cartes/{projet}', [ProjetController::class, 'show'])->name('cartes.show');
        Route::patch('/cartes/{projet}', [ProjetController::class, 'update'])->name('cartes.update');
        Route::delete('/cartes/{projet}', [ProjetController::class, 'destroy'])->name('cartes.destroy');
        Route::post('/move', [ProjetController::class, 'move'])->name('cartes.move');
        Route::post('/cartes/{projet}/membres', [ProjetController::class, 'syncMembres'])->name('cartes.membres');
        Route::post('/cartes/{projet}/etiquettes', [ProjetController::class, 'syncEtiquettes'])->name('cartes.etiquettes');
        Route::post('/etiquettes', [ProjetController::class, 'storeEtiquette'])->name('etiquettes.store');
        Route::post('/cartes/{projet}/checklists', [ProjetController::class, 'storeChecklist'])->name('cartes.checklists');
        Route::post('/checklists/{checklist}/items', [ProjetController::class, 'storeChecklistItem'])->name('checklists.items');
        Route::patch('/checklist-items/{item}/toggle', [ProjetController::class, 'toggleChecklistItem'])->name('checklist-items.toggle');
        Route::delete('/checklist-items/{item}', [ProjetController::class, 'destroyChecklistItem'])->name('checklist-items.destroy');
        Route::post('/cartes/{projet}/commentaires', [ProjetController::class, 'storeCommentaire'])->name('cartes.commentaires');
        Route::post('/cartes/{projet}/pieces-jointes', [ProjetController::class, 'storePieceJointe'])->name('cartes.pieces');
        Route::delete('/pieces-jointes/{piece}', [ProjetController::class, 'destroyPieceJointe'])->name('pieces.destroy');
    });
    Route::get('/validation-achats', function () {
        return redirect()->route('gestion-projet.index');
    });

    Route::resource('evenements', EvenementController::class)->except(['show']);
    Route::get('/calendrier-editorial', [CalendrierEditorialController::class, 'index'])->name('calendrier-editorial');
    Route::post('/calendrier-editorial', [CalendrierEditorialController::class, 'store'])->name('calendrier-editorial.store');
    Route::put('/calendrier-editorial/{editorialEvent}', [CalendrierEditorialController::class, 'update'])->name('calendrier-editorial.update');
    Route::delete('/calendrier-editorial/{editorialEvent}', [CalendrierEditorialController::class, 'destroy'])->name('calendrier-editorial.destroy');
    Route::get('/statistiques', [StatistiqueController::class, 'index'])->name('statistiques');
    Route::get('/parametres/systeme', fn () => view('pages.placeholder', ['title' => 'Configuration système', 'subtitle' => 'Paramètres de l\'application']))->name('parametres.systeme');

    Route::resource('users', UserController::class)
        ->middleware('role:super_admin,administrateur');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
