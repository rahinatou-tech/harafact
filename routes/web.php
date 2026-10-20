<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\FactureController;
use App\Http\Controllers\PaiementController;
use App\Http\Controllers\ProduitController;
use App\Http\Controllers\UtilisateurController;
use App\Http\Controllers\ConfigController;

// ─── AUTH ─────────────────────────────────────────────────────
Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout',[AuthController::class, 'logout'])->name('logout');



Route::get('/forgot-password', [AuthController::class, 'showForgotForm'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
// Afficher le formulaire (GET avec token dans l'URL)
Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])
     ->name('password.reset');

// Traiter le formulaire (POST)
Route::post('/reset-password', [AuthController::class, 'resetPassword'])
     ->name('password.update');



Route::get('/facture/public/{token}', [App\Http\Controllers\FacturePublicController::class, 'show'])
     ->name('facture.public');



// ─── APP PROTÉGÉE ─────────────────────────────────────────────
Route::middleware(['auth'])->group(function () {

    // Dashboard — Tout le monde
    Route::get('/',          [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // ===== FACTURES — LECTURE SEULE (tous) + CRUD (Employé) =====
    Route::get('/factures',                [FactureController::class, 'index'])->name('factures.index');
    
    // Create AVANT show pour éviter le conflit avec {facture}
    Route::middleware([\App\Http\Middleware\IsEmploye::class])->get('/factures/create', [FactureController::class, 'create'])->name('factures.create');
    
    Route::get('/factures/{facture}',      [FactureController::class, 'show'])->name('factures.show');
    Route::get('/factures/{facture}/pdf',  [FactureController::class, 'generatePdf'])->name('factures.pdf');
    
    // Créer/Modifier (Employé seulement)
    Route::middleware([\App\Http\Middleware\IsEmploye::class])->group(function () {
        Route::post('/factures',                [FactureController::class, 'store'])->name('factures.store');
        Route::get('/factures/{facture}/edit',  [FactureController::class, 'edit'])->name('factures.edit');
        Route::put('/factures/{facture}',       [FactureController::class, 'update'])->name('factures.update');
        Route::delete('/factures/{facture}',    [FactureController::class, 'destroy'])->name('factures.destroy');
        Route::post('/factures/{facture}/dupliquer', [FactureController::class, 'dupliquer'])->name('factures.dupliquer');
        Route::post('/factures/calcul-apercu',       [FactureController::class, 'calculerApercu'])->name('factures.calcul');
    });
    
    // Valider/Rejeter (Comptable seulement)
    Route::middleware([\App\Http\Middleware\IsComptable::class])->group(function () {
        Route::post('/factures/{facture}/valider', [FactureController::class, 'valider'])->name('factures.valider');
        Route::post('/factures/{facture}/rejeter', [FactureController::class, 'rejeter'])->name('factures.rejeter');
        Route::post('/factures/{facture}/envoyer', [FactureController::class, 'envoyer'])->name('factures.envoyer');
    });

    // ===== CLIENTS (Employé seulement) =====
    Route::middleware([\App\Http\Middleware\IsEmploye::class])->group(function () {
        Route::resource('clients', ClientController::class);
    });

    // ===== PRODUITS (Employé seulement) =====
    Route::middleware([\App\Http\Middleware\IsEmploye::class])->group(function () {
        Route::get('produits/catalogue', [ProduitController::class, 'catalogue'])->name('produits.catalogue');
        Route::resource('produits', ProduitController::class);
    });

    // ===== DEVIS (Employé seulement) =====
    Route::middleware([\App\Http\Middleware\IsEmploye::class])->group(function () {
        Route::resource('devis', \App\Http\Controllers\DevisController::class);
        Route::get('devis/{devis}/pdf', [\App\Http\Controllers\DevisController::class, 'generatePdf'])->name('devis.pdf');
        Route::post('devis/{devis}/convertir', [\App\Http\Controllers\DevisController::class, 'convertirEnFacture'])->name('devis.convertir');
    });

    // ===== PAIEMENTS (Comptable seulement) =====
    Route::middleware([\App\Http\Middleware\IsComptable::class])->group(function () {
        Route::get('/paiements',            [PaiementController::class, 'index'])->name('paiements.index');
        Route::post('/paiements',           [PaiementController::class, 'store'])->name('paiements.store');
        Route::delete('/paiements/{paiement}', [PaiementController::class, 'destroy'])->name('paiements.destroy');
    });

    // ===== UTILISATEURS (Admin seulement) =====
    Route::middleware([\App\Http\Middleware\IsAdmin::class])->group(function () {
        Route::resource('utilisateurs', UtilisateurController::class);
    });

    // ===== CONFIG (Admin seulement) =====
    Route::middleware([\App\Http\Middleware\IsAdmin::class])->group(function () {
        Route::get('/config',  [ConfigController::class, 'index'])->name('config.index');
        Route::post('/config', [ConfigController::class, 'update'])->name('config.update');
    });
});