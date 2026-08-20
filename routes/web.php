<?php

use App\Http\Controllers\AccesController;
use App\Http\Controllers\ActiviteController;
use App\Http\Controllers\BudgetAnnuelController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CalendrierEditorialController;
use App\Http\Controllers\CampagneController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepenseController;
use App\Http\Controllers\EvenementController;
use App\Http\Controllers\FournisseurController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NoteController;
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
    Route::get('/notifications/poll', [NotificationController::class, 'poll'])->name('notifications.poll');
    Route::post('/notifications/mark-read', [NotificationController::class, 'markRead'])->name('notifications.mark-read');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::delete('/notifications', [NotificationController::class, 'destroyAll'])->name('notifications.destroy-all');
    Route::get('/notes', [NoteController::class, 'show'])->name('notes.show');
    Route::put('/notes', [NoteController::class, 'update'])->name('notes.update');
    Route::post('/notes/image', [NoteController::class, 'uploadImage'])->name('notes.image');
    Route::get('/activite', [ActiviteController::class, 'index'])
        ->middleware('permission:activite.view')
        ->name('activite.index');

    Route::middleware('permission:budget_annuel.view')->group(function () {
        Route::resource('budget-annuels', BudgetAnnuelController::class)->except(['show']);
    });
    Route::middleware('permission:budget_mensuel.view')->group(function () {
        Route::resource('budgets', BudgetController::class)->except(['show']);
    });
    Route::middleware('permission:depenses.view')->group(function () {
        Route::resource('depenses', DepenseController::class)->except(['show']);
    });
    Route::middleware('permission:fournisseurs.view')->group(function () {
        Route::resource('fournisseurs', FournisseurController::class)->except(['show']);
    });
    Route::middleware('permission:campagnes.view')->group(function () {
        Route::resource('campagnes', CampagneController::class)->except(['show']);
    });
    Route::prefix('stocks')->name('stocks.')->group(function () {
        Route::middleware('permission:stocks_mouvements.view')->group(function () {
            Route::resource('mouvements', StockMouvementController::class)
                ->parameters(['mouvements' => 'mouvement'])
                ->except(['show']);
        });
    });
    Route::middleware('permission:stocks.view')->group(function () {
        Route::resource('stocks', StockController::class)->except(['show']);
    });

    Route::middleware('permission:gestion_projet.view')->prefix('gestion-projet')->name('gestion-projet.')->group(function () {
        Route::get('/', [ProjetController::class, 'index'])->name('index');
        Route::post('/listes', [ProjetController::class, 'storeListe'])->name('listes.store');
        Route::post('/listes/reorder', [ProjetController::class, 'reorderListes'])->name('listes.reorder');
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
        Route::delete('/checklists/{checklist}', [ProjetController::class, 'destroyChecklist'])->name('checklists.destroy');
        Route::post('/checklists/{checklist}/items', [ProjetController::class, 'storeChecklistItem'])->name('checklists.items');
        Route::patch('/checklist-items/{item}/toggle', [ProjetController::class, 'toggleChecklistItem'])->name('checklist-items.toggle');
        Route::delete('/checklist-items/{item}', [ProjetController::class, 'destroyChecklistItem'])->name('checklist-items.destroy');
        Route::post('/cartes/{projet}/commentaires', [ProjetController::class, 'storeCommentaire'])->name('cartes.commentaires');
        Route::patch('/commentaires/{commentaire}', [ProjetController::class, 'updateCommentaire'])->name('commentaires.update');
        Route::post('/commentaires/{commentaire}/reactions', [ProjetController::class, 'toggleCommentaireReaction'])->name('commentaires.reactions');
        Route::delete('/commentaire-images/{image}', [ProjetController::class, 'destroyCommentaireImage'])->name('commentaire-images.destroy');
        Route::post('/cartes/{projet}/pieces-jointes', [ProjetController::class, 'storePieceJointe'])->name('cartes.pieces');
        Route::delete('/pieces-jointes/{piece}', [ProjetController::class, 'destroyPieceJointe'])->name('pieces.destroy');
    });
    Route::get('/validation-achats', function () {
        return redirect()->route('gestion-projet.index');
    });

    Route::middleware('permission:evenements.view')->group(function () {
        Route::resource('evenements', EvenementController::class)->except(['show']);
    });
    Route::middleware('permission:calendrier_editorial.view')->group(function () {
        Route::get('/calendrier-editorial', [CalendrierEditorialController::class, 'index'])->name('calendrier-editorial');
        Route::get('/calendrier-editorial/search', [CalendrierEditorialController::class, 'search'])->name('calendrier-editorial.search');
        Route::post('/calendrier-editorial', [CalendrierEditorialController::class, 'store'])->name('calendrier-editorial.store');
        Route::put('/calendrier-editorial/{editorialEvent}', [CalendrierEditorialController::class, 'update'])->name('calendrier-editorial.update');
        Route::delete('/calendrier-editorial/{editorialEvent}', [CalendrierEditorialController::class, 'destroy'])->name('calendrier-editorial.destroy');
    });
    Route::get('/statistiques', [StatistiqueController::class, 'index'])
        ->middleware('permission:statistiques.view')
        ->name('statistiques');
    Route::get('/parametres/systeme', fn () => view('pages.placeholder', ['title' => 'Configuration système', 'subtitle' => 'Paramètres de l\'application']))
        ->middleware('permission:parametres.systeme')
        ->name('parametres.systeme');

    Route::prefix('acces')->name('acces.')->middleware('role:super_admin')->group(function () {
        Route::get('/', [AccesController::class, 'index'])->name('index');
        Route::post('/roles', [AccesController::class, 'storeRole'])->name('roles.store');
        Route::get('/roles/{role}', [AccesController::class, 'editRole'])->name('roles.edit');
        Route::put('/roles/{role}', [AccesController::class, 'updateRole'])->name('roles.update');
        Route::delete('/roles/{role}', [AccesController::class, 'destroyRole'])->name('roles.destroy');
        Route::get('/users/{user}', [AccesController::class, 'editUser'])->name('users.edit');
        Route::put('/users/{user}', [AccesController::class, 'updateUser'])->name('users.update');
    });

    Route::resource('users', UserController::class)
        ->middleware('permission:users.view');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
