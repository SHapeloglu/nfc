<?php

use App\Http\Controllers\CurrentUserController;
use App\Http\Controllers\NfcCardListController;
use App\Http\Controllers\NfcCardWriteController;
use App\Http\Controllers\NfcProjectController;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;

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
// NFC_BRIDGE_CARDS_API_V1
/**
 * PC yardımcı programının kart yazma ve yeniden okuma sonuçlarını bildirdiği uç noktalar.
 * Jetonun 'nfc:write' yetkisi olmalıdır, yoksa 403. Yönetici ve kart/bağlantı doğrulaması
 * NfcCardWriteController içinde yapılır (web akışıyla aynı kurallar).
 */
Route::get('/nfc/cards', NfcCardListController::class)->middleware([
    'auth:sanctum',
    'throttle:30,1',
])->name('api.nfc.cards.index');
Route::post('/nfc/cards/{card}/written', [NfcCardWriteController::class, 'written'])->middleware([
    'auth:sanctum',
    CheckForAnyAbility::class.':nfc:write',
    'throttle:30,1',
])->name('api.nfc.cards.written');
Route::post('/nfc/cards/{card}/verified', [NfcCardWriteController::class, 'verified'])->middleware([
    'auth:sanctum',
    CheckForAnyAbility::class.':nfc:write',
    'throttle:30,1',
])->name('api.nfc.cards.verified');
