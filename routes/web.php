<?php

use App\Http\Controllers\AccesController;
use App\Http\Controllers\ActiviteController;
use App\Http\Controllers\BudgetAnnuelController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CalendrierEditorialController;
use App\Http\Controllers\CampagneController;
use App\Http\Controllers\Crm\CandidateController as CrmCandidateController;
use App\Http\Controllers\Crm\DashboardController as CrmDashboardController;
use App\Http\Controllers\Crm\ExportController as CrmExportController;
use App\Http\Controllers\Crm\ImportController as CrmImportController;
use App\Http\Controllers\Crm\PipelineController as CrmPipelineController;
use App\Http\Controllers\Crm\SettingsController as CrmSettingsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartementController;
use App\Http\Controllers\DepenseController;
use App\Http\Controllers\EvenementController;
use App\Http\Controllers\FournisseurController;
use App\Http\Controllers\LinkRedirectController;
use App\Http\Controllers\NavbarMenuController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjetController;
use App\Http\Controllers\StatistiqueController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockMouvementController;
use App\Http\Controllers\TrackedLinkController;
use App\Http\Controllers\UserController;
use App\Support\ResourceRoutes;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->to(auth()->user()->homeUrl())
        : redirect()->route('login');
});

Route::get('/l/{slug}', LinkRedirectController::class)
    ->where('slug', '[A-Za-z0-9\-_]+')
    ->name('liens.redirect');

Route::middleware(['auth', 'departement.menu'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:dashboard.view')
        ->name('dashboard');
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

    ResourceRoutes::register('budget-annuels', BudgetAnnuelController::class, 'budget_annuel');
    ResourceRoutes::register('budgets', BudgetController::class, 'budget_mensuel');
    ResourceRoutes::register('depenses', DepenseController::class, 'depenses');
    Route::post('depenses/categories', [DepenseController::class, 'storeCategorie'])
        ->middleware('permission:depenses.create')
        ->name('depenses.categories.store');
    ResourceRoutes::register('fournisseurs', FournisseurController::class, 'fournisseurs');
    ResourceRoutes::register('campagnes', CampagneController::class, 'campagnes');
    Route::middleware('permission:suivi_liens.view')->group(function () {
        Route::get('suivi-liens', [TrackedLinkController::class, 'index'])->name('suivi-liens.index');
        Route::get('suivi-liens/create', [TrackedLinkController::class, 'create'])
            ->middleware('permission:suivi_liens.create')
            ->name('suivi-liens.create');
        Route::post('suivi-liens', [TrackedLinkController::class, 'store'])
            ->middleware('permission:suivi_liens.create')
            ->name('suivi-liens.store');
        Route::get('suivi-liens/{suivi_lien}', [TrackedLinkController::class, 'show'])->name('suivi-liens.show');
        Route::get('suivi-liens/{suivi_lien}/qr', [TrackedLinkController::class, 'qr'])
            ->name('suivi-liens.qr');
        Route::get('suivi-liens/{suivi_lien}/qr/download', [TrackedLinkController::class, 'downloadQr'])
            ->name('suivi-liens.qr.download');
        Route::get('suivi-liens/{suivi_lien}/edit', [TrackedLinkController::class, 'edit'])
            ->middleware('permission:suivi_liens.update')
            ->name('suivi-liens.edit');
        Route::put('suivi-liens/{suivi_lien}', [TrackedLinkController::class, 'update'])
            ->middleware('permission:suivi_liens.update')
            ->name('suivi-liens.update');
        Route::delete('suivi-liens/{suivi_lien}', [TrackedLinkController::class, 'destroy'])
            ->middleware('permission:suivi_liens.delete')
            ->name('suivi-liens.destroy');
    });

    Route::middleware('permission:crm.view')->prefix('crm')->name('crm.')->group(function () {
        Route::get('/', CrmDashboardController::class)->name('dashboard');
        Route::get('/pipeline', CrmPipelineController::class)->name('pipeline');
        Route::get('/export', [CrmExportController::class, 'index'])->name('export');
        Route::post('/export', [CrmExportController::class, 'download'])->name('export.download');
        Route::get('/parametres', [CrmSettingsController::class, 'index'])->name('settings');
        Route::post('/parametres/rentrees', [CrmSettingsController::class, 'storeIntake'])
            ->middleware('permission:crm.update')
            ->name('settings.intakes.store');
        Route::patch('/parametres/rentrees/{intake}', [CrmSettingsController::class, 'updateIntake'])
            ->middleware('permission:crm.update')
            ->name('settings.intakes.update');
        Route::patch('/parametres/rentrees/{intake}/toggle', [CrmSettingsController::class, 'toggleIntake'])
            ->middleware('permission:crm.update')
            ->name('settings.intakes.toggle');
        Route::delete('/parametres/rentrees/{intake}', [CrmSettingsController::class, 'destroyIntake'])
            ->middleware('permission:crm.update')
            ->name('settings.intakes.destroy');
        Route::post('/parametres/programmes', [CrmSettingsController::class, 'storeProgramme'])
            ->middleware('permission:crm.update')
            ->name('settings.programmes.store');
        Route::patch('/parametres/programmes/{programme}/toggle', [CrmSettingsController::class, 'toggleProgramme'])
            ->middleware('permission:crm.update')
            ->name('settings.programmes.toggle');
        Route::delete('/parametres/programmes/{programme}', [CrmSettingsController::class, 'destroyProgramme'])
            ->middleware('permission:crm.update')
            ->name('settings.programmes.destroy');
        Route::post('/parametres/documents', [CrmSettingsController::class, 'storeDocumentType'])
            ->middleware('permission:crm.update')
            ->name('settings.document-types.store');
        Route::patch('/parametres/documents/{documentType}/required', [CrmSettingsController::class, 'toggleDocumentTypeRequired'])
            ->middleware('permission:crm.update')
            ->name('settings.document-types.toggle-required');
        Route::patch('/parametres/documents/{documentType}/toggle', [CrmSettingsController::class, 'toggleDocumentType'])
            ->middleware('permission:crm.update')
            ->name('settings.document-types.toggle');
        Route::delete('/parametres/documents/{documentType}', [CrmSettingsController::class, 'destroyDocumentType'])
            ->middleware('permission:crm.update')
            ->name('settings.document-types.destroy');
        Route::post('/parametres/import', [CrmImportController::class, 'store'])
            ->middleware('permission:crm.update')
            ->name('settings.import');
        Route::delete('/parametres/donnees', [CrmImportController::class, 'destroyAll'])
            ->middleware('permission:crm.update')
            ->name('settings.destroy-all');

        Route::get('/candidats', [CrmCandidateController::class, 'index'])->name('candidats.index');
        Route::get('/abandons', [CrmCandidateController::class, 'index'])->name('abandons');
        Route::get('/candidats/create', [CrmCandidateController::class, 'create'])
            ->middleware('permission:crm.create')
            ->name('candidats.create');
        Route::post('/candidats', [CrmCandidateController::class, 'store'])
            ->middleware('permission:crm.create')
            ->name('candidats.store');
        Route::get('/candidats/{candidat}', [CrmCandidateController::class, 'show'])->name('candidats.show');
        Route::get('/candidats/{candidat}/documents/{document}/fichier', [CrmCandidateController::class, 'downloadDocument'])->name('candidats.documents.download');
        Route::get('/candidats/{candidat}/edit', [CrmCandidateController::class, 'edit'])
            ->middleware('permission:crm.update')
            ->name('candidats.edit');
        Route::put('/candidats/{candidat}', [CrmCandidateController::class, 'update'])
            ->middleware('permission:crm.update')
            ->name('candidats.update');
        Route::delete('/candidats/{candidat}', [CrmCandidateController::class, 'destroy'])
            ->middleware('permission:crm.delete')
            ->name('candidats.destroy');
        Route::post('/candidats/{candidat}/abandon', [CrmCandidateController::class, 'abandon'])
            ->middleware('permission:crm.update')
            ->name('candidats.abandon');
        Route::post('/candidats/{candidat}/notes', [CrmCandidateController::class, 'storeNote'])
            ->middleware('permission:crm.update')
            ->name('candidats.notes.store');
        Route::post('/candidats/{candidat}/interactions', [CrmCandidateController::class, 'storeInteraction'])
            ->middleware('permission:crm.update')
            ->name('candidats.interactions.store');
        Route::delete('/candidats/{candidat}/interactions/{interaction}', [CrmCandidateController::class, 'destroyInteraction'])
            ->middleware('permission:crm.update')
            ->name('candidats.interactions.destroy');
        Route::delete('/candidats/{candidat}/documents/{document}', [CrmCandidateController::class, 'destroyDocument'])
            ->middleware('permission:crm.update')
            ->name('candidats.documents.destroy');
        Route::post('/candidats/{candidat}/documents', [CrmCandidateController::class, 'storeDocuments'])
            ->middleware('permission:crm.update')
            ->name('candidats.documents.store');
    });

    Route::prefix('stocks')->name('stocks.')->group(function () {
        ResourceRoutes::register('mouvements', StockMouvementController::class, 'stocks_mouvements', [
            'parameters' => ['mouvements' => 'mouvement'],
        ]);
    });
    ResourceRoutes::register('stocks', StockController::class, 'stocks');

    Route::middleware('permission:gestion_projet.view')->prefix('gestion-projet')->name('gestion-projet.')->group(function () {
        Route::get('/', [ProjetController::class, 'index'])->name('index');
        Route::get('/cartes/{projet}', [ProjetController::class, 'show'])->name('cartes.show');
        Route::get('/pieces-jointes/{piece}/fichier', [ProjetController::class, 'downloadPieceJointe'])->name('pieces.download');

        Route::middleware('permission:gestion_projet.create')->group(function () {
            Route::post('/listes', [ProjetController::class, 'storeListe'])->name('listes.store');
            Route::post('/cartes', [ProjetController::class, 'store'])->name('cartes.store');
            Route::post('/etiquettes', [ProjetController::class, 'storeEtiquette'])->name('etiquettes.store');
            Route::post('/cartes/{projet}/checklists', [ProjetController::class, 'storeChecklist'])->name('cartes.checklists');
            Route::post('/checklists/{checklist}/items', [ProjetController::class, 'storeChecklistItem'])->name('checklists.items');
            Route::post('/cartes/{projet}/commentaires', [ProjetController::class, 'storeCommentaire'])->name('cartes.commentaires');
            Route::post('/cartes/{projet}/pieces-jointes', [ProjetController::class, 'storePieceJointe'])->name('cartes.pieces');
        });

        Route::middleware('permission:gestion_projet.update')->group(function () {
            Route::post('/listes/reorder', [ProjetController::class, 'reorderListes'])->name('listes.reorder');
            Route::patch('/listes/{liste}', [ProjetController::class, 'updateListe'])->name('listes.update');
            Route::post('/background', [ProjetController::class, 'updateBackground'])->name('background');
            Route::patch('/cartes/{projet}', [ProjetController::class, 'update'])->name('cartes.update');
            Route::post('/move', [ProjetController::class, 'move'])->name('cartes.move');
            Route::post('/cartes/{projet}/membres', [ProjetController::class, 'syncMembres'])->name('cartes.membres');
            Route::post('/cartes/{projet}/etiquettes', [ProjetController::class, 'syncEtiquettes'])->name('cartes.etiquettes');
            Route::patch('/checklist-items/{item}/toggle', [ProjetController::class, 'toggleChecklistItem'])->name('checklist-items.toggle');
            Route::patch('/commentaires/{commentaire}', [ProjetController::class, 'updateCommentaire'])->name('commentaires.update');
            Route::post('/commentaires/{commentaire}/reactions', [ProjetController::class, 'toggleCommentaireReaction'])->name('commentaires.reactions');
        });

        Route::middleware('permission:gestion_projet.delete')->group(function () {
            Route::delete('/listes/{liste}', [ProjetController::class, 'destroyListe'])->name('listes.destroy');
            Route::delete('/cartes/{projet}', [ProjetController::class, 'destroy'])->name('cartes.destroy');
            Route::delete('/checklists/{checklist}', [ProjetController::class, 'destroyChecklist'])->name('checklists.destroy');
            Route::delete('/checklist-items/{item}', [ProjetController::class, 'destroyChecklistItem'])->name('checklist-items.destroy');
            Route::delete('/commentaire-images/{image}', [ProjetController::class, 'destroyCommentaireImage'])->name('commentaire-images.destroy');
            Route::delete('/pieces-jointes/{piece}', [ProjetController::class, 'destroyPieceJointe'])->name('pieces.destroy');
        });
    });
    Route::get('/validation-achats', function () {
        return redirect()->route('gestion-projet.index');
    });

    ResourceRoutes::register('evenements', EvenementController::class, 'evenements');
    Route::middleware('permission:calendrier_editorial.view')->group(function () {
        Route::get('/calendrier-editorial', [CalendrierEditorialController::class, 'index'])->name('calendrier-editorial');
        Route::get('/calendrier-editorial/search', [CalendrierEditorialController::class, 'search'])->name('calendrier-editorial.search');
    });
    Route::post('/calendrier-editorial', [CalendrierEditorialController::class, 'store'])
        ->middleware('permission:calendrier_editorial.create')
        ->name('calendrier-editorial.store');
    Route::put('/calendrier-editorial/{editorialEvent}', [CalendrierEditorialController::class, 'update'])
        ->middleware('permission:calendrier_editorial.update')
        ->name('calendrier-editorial.update');
    Route::delete('/calendrier-editorial/{editorialEvent}', [CalendrierEditorialController::class, 'destroy'])
        ->middleware('permission:calendrier_editorial.delete')
        ->name('calendrier-editorial.destroy');
    Route::get('/statistiques', [StatistiqueController::class, 'index'])
        ->middleware('permission:statistiques.view')
        ->name('statistiques');
    Route::get('/parametres/systeme', fn () => view('pages.placeholder', ['title' => 'Configuration système', 'subtitle' => 'Paramètres de l\'application']))
        ->middleware('permission:parametres.systeme')
        ->name('parametres.systeme');

    Route::prefix('navbar')->name('navbar.')->middleware('role:super_admin')->group(function () {
        Route::get('/', [NavbarMenuController::class, 'index'])->name('index');
        Route::put('/', [NavbarMenuController::class, 'update'])->name('update');
    });

    Route::prefix('acces')->name('acces.')->middleware('role:super_admin')->group(function () {
        Route::get('/', [AccesController::class, 'index'])->name('index');
        Route::post('/roles', [AccesController::class, 'storeRole'])->name('roles.store');
        Route::get('/roles/{role}', [AccesController::class, 'editRole'])->name('roles.edit');
        Route::put('/roles/{role}', [AccesController::class, 'updateRole'])->name('roles.update');
        Route::delete('/roles/{role}', [AccesController::class, 'destroyRole'])->name('roles.destroy');
        Route::get('/users/{user}', [AccesController::class, 'editUser'])->name('users.edit');
        Route::put('/users/{user}', [AccesController::class, 'updateUser'])->name('users.update');
    });

    Route::post('/departement-actif', [DepartementController::class, 'switch'])->name('departement.switch');

    Route::middleware('permission:users.view')->group(function () {
        Route::resource('users', UserController::class)->only(['index', 'show']);
    });
    Route::middleware('permission:users.create')->group(function () {
        Route::post('/departements', [DepartementController::class, 'store'])->name('departements.store');
        Route::resource('users', UserController::class)->only(['create', 'store']);
    });
    Route::middleware('permission:users.update')->group(function () {
        Route::put('/departements/{departement}', [DepartementController::class, 'update'])->name('departements.update');
        Route::resource('users', UserController::class)->only(['edit', 'update']);
    });
    Route::middleware('permission:users.delete')->group(function () {
        Route::delete('/departements/{departement}', [DepartementController::class, 'destroy'])->name('departements.destroy');
        Route::resource('users', UserController::class)->only(['destroy']);
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
