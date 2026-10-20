<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FactureController;
use App\Http\Controllers\DevisController;
use App\Http\Controllers\PaiementController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProduitController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\UtilisateurController;
use App\Http\Controllers\Api\RecurrenceController;

// ══════════════════════════════════════════════════════
//  AUTH (publiques)
// ══════════════════════════════════════════════════════
Route::prefix('auth')->group(function () {
    Route::post('login',                [AuthController::class, 'apiLogin']);
    Route::post('refresh',              [AuthController::class, 'apiRefresh']);
    Route::post('mot-de-passe/demande', [AuthController::class, 'apiDemandeReinit']);
    Route::post('mot-de-passe/reset',   [AuthController::class, 'apiReinitPassword']);
});

// ══════════════════════════════════════════════════════
//  ROUTES PROTÉGÉES  ← 'api.auth' au lieu de 'auth:api'
// ══════════════════════════════════════════════════════
Route::middleware('api.auth')->group(function () {

    // Auth
    Route::post('auth/logout', [AuthController::class, 'apiLogout']);
    Route::get('auth/me',      [AuthController::class, 'apiMe']);

    // Dashboard
    Route::get('dashboard',    [DashboardController::class, 'apiIndex']);

    // ── FACTURES ───────────────────────────────────────
    Route::prefix('factures')->group(function () {
        Route::get('/',                [FactureController::class, 'apiIndex']);
        Route::post('/',               [FactureController::class, 'apiStore']);
        Route::post('/calcul-apercu',  [FactureController::class, 'apiCalculerApercu']);
        Route::get('/{id}',            [FactureController::class, 'apiShow']);
        Route::put('/{id}',            [FactureController::class, 'apiUpdate']);
        Route::delete('/{id}',         [FactureController::class, 'apiDestroy']);
        Route::post('/{id}/soumettre', [FactureController::class, 'apiSoumettre']);
        Route::post('/{id}/valider',   [FactureController::class, 'apiValider']);
        Route::post('/{id}/rejeter',   [FactureController::class, 'apiRejeter']);
        Route::post('/{id}/envoyer',   [FactureController::class, 'apiEnvoyer']);
        Route::post('/{id}/dupliquer', [FactureController::class, 'apiDupliquer']);
        Route::get('/{id}/pdf-url',    [FactureController::class, 'apiPdfUrl']);
    });

    // ── DEVIS ──────────────────────────────────────────
    Route::prefix('devis')->group(function () {
        Route::get('/',                [DevisController::class, 'apiIndex']);
        Route::post('/',               [DevisController::class, 'apiStore']);
        Route::get('/{id}',            [DevisController::class, 'apiShow']);
        Route::put('/{id}',            [DevisController::class, 'apiUpdate']);
        Route::delete('/{id}',         [DevisController::class, 'apiDestroy']);
        Route::post('/{id}/envoyer',   [DevisController::class, 'apiEnvoyer']);
        Route::post('/{id}/convertir', [DevisController::class, 'apiConvertir']);
        Route::post('/{id}/dupliquer', [DevisController::class, 'apiDupliquer']);
    });

    // ── PAIEMENTS (Comptable) ──────────────────────────
   Route::get('paiements/factures-a-suivre', [PaiementController::class, 'apiFActuresASuivre'])
    ->middleware('role:Comptable,DG,Admin');

    Route::prefix('paiements')->middleware('role:Comptable,DG,Admin')->group(function () {
        Route::get('/',        [PaiementController::class, 'apiIndex']);
        Route::post('/',       [PaiementController::class, 'apiStore']);
        Route::delete('/{id}', [PaiementController::class, 'apiDestroy']);
    });

    // ── RÉCURRENCES ────────────────────────────────────
    Route::prefix('recurrences')->group(function () {
        Route::get('/',                [RecurrenceController::class, 'index']);
        Route::post('/',               [RecurrenceController::class, 'store']);
        Route::get('/{id}',            [RecurrenceController::class, 'show']);
        Route::put('/{id}',            [RecurrenceController::class, 'update']);
        Route::delete('/{id}',         [RecurrenceController::class, 'destroy']);
        Route::post('/{id}/pause',     [RecurrenceController::class, 'pause']);
        Route::post('/{id}/reprendre', [RecurrenceController::class, 'reprendre']);
        Route::get('/{id}/factures',   [RecurrenceController::class, 'factures']);
    });

    // ── CLIENTS ────────────────────────────────────────
    Route::prefix('clients')->group(function () {
        Route::get('/',        [ClientController::class, 'apiIndex']);
        Route::post('/',       [ClientController::class, 'apiStore']);
        Route::get('/{id}',    [ClientController::class, 'apiShow']);
        Route::put('/{id}',    [ClientController::class, 'apiUpdate']);
        Route::delete('/{id}', [ClientController::class, 'apiDestroy']);
    });

    // ── PRODUITS ───────────────────────────────────────
    Route::prefix('produits')->group(function () {
        Route::get('/',        [ProduitController::class, 'apiIndex']);
        Route::post('/',       [ProduitController::class, 'apiStore']);
        Route::get('/{id}',    [ProduitController::class, 'apiShow']);
        Route::put('/{id}',    [ProduitController::class, 'apiUpdate']);
        Route::delete('/{id}', [ProduitController::class, 'apiDestroy']);
    });

    // ── ADMIN ──────────────────────────────────────────
    Route::middleware('role:Admin,DG')->group(function () {
        Route::prefix('utilisateurs')->group(function () {
            Route::get('/',        [UtilisateurController::class, 'apiIndex']);
            Route::post('/',       [UtilisateurController::class, 'apiStore']);
            Route::get('/{id}',    [UtilisateurController::class, 'apiShow']);
            Route::put('/{id}',    [UtilisateurController::class, 'apiUpdate']);
            Route::delete('/{id}', [UtilisateurController::class, 'apiDestroy']);
        });
        Route::get('config', [ConfigController::class, 'apiShow']);
        Route::put('config', [ConfigController::class, 'apiUpdate']);
    });

    // ── FCM TOKEN ──────────────────────────────────────
    Route::post('fcm/token', [AuthController::class, 'updateFcmToken']);
});