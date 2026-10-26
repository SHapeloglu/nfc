<?php

use App\Http\Controllers\CurrentUserController;
use App\Http\Controllers\NfcProjectController;
use Illuminate\Support\Facades\Route;

/**
 * Oturumdaki kullanıcıyı JSON döner. Sanctum kimlik doğrulaması (auth:sanctum) gerekir; istek sınırı yoktur.
 * Kimliksiz istek 401 alır (JSON cevabı; bootstrap/app.php).
 */
Route::get('/user', CurrentUserController::class)->middleware('auth:sanctum');
// NFC_BRIDGE_PROJECTS_API_V1
/**
 * NFC köprüsü için yönetici kullanıcının projelerini JSON döner (GET /api/nfc/projects).
 * Sanctum (auth:sanctum) ve dakikada 30 istek sınırı vardır. Yönetici kontrolü (is_admin) rota içinde yapılır;
 * ayrıca jetonun 'nfc:read' yetkisi olmalıdır, yoksa 403. Kimliksiz istek 401 alır.
 */
Route::get('/nfc/projects', NfcProjectController::class)->middleware([
    'auth:sanctum',
    'throttle:30,1',
])->name('api.nfc.projects');
