<?php
// Uygulamanın ana web rotaları: QR üretici, QR tasarım kaydı, logo yükleme ve Google yorum araması burada tanımlıdır.
// Korumalar iki türlüdür: giriş (auth) ve istek sınırı (throttle). Yönetici (is_admin) kontrolleri denetleyicilerde yapılır.
// Başlık satırları (// QR_..._ROUTES vb.) olduğu gibi bırakıldı; bunları bir araç kullanıyor olabilir.

use Illuminate\Support\Facades\Route;

/**
 * Ana sayfa (karşılama ekranı). Giriş istemez.
 */
Route::get('/', function () {
    return view('welcome');
});

// QR_GENERATOR_ROUTES
/**
 * QR üretici sayfası. Giriş istemez.
 */
Route::get('/qr', [
    \App\Http\Controllers\QrGeneratorController::class, 'index'
])->name('qr.index');

/**
 * QR kodu üretir (PNG veya SVG). Kayıt yapmaz; dosya doğrudan cevap olarak döner. Giriş istemez, dakikada 30 istek sınırı vardır.
 */
Route::post('/qr/generate', [
    \App\Http\Controllers\QrGeneratorController::class, 'generate'
])->middleware('throttle:30,1')->name('qr.generate');

Route::get('/wifi-qr', [
    \App\Http\Controllers\WifiQrController::class, 'index',
])->name('wifi.qr.index');

Route::post('/wifi-qr/generate', [
    \App\Http\Controllers\WifiQrController::class, 'generate',
])->middleware('throttle:30,1')->name('wifi.qr.generate');

// QR_DESIGN_STORAGE
/**
 * Kullanıcının kayıtlı QR tasarımlarını JSON olarak listeler. Giriş middleware'i yoktur; yönetici kontrolü denetleyicidedir (yönetici değilse 403).
 */
Route::get('/qr/designs', [
    \App\Http\Controllers\QrDesignController::class, 'index'
])->middleware('throttle:60,1')->name('qr.designs.index');

/**
 * QR tasarımını kaydeder (id yoksa yeni, varsa güncelleme). Yönetici kontrolü denetleyicidedir; dakikada 30 istek sınırı vardır.
 */
Route::post('/qr/designs', [
    \App\Http\Controllers\QrDesignController::class, 'store'
])->middleware('throttle:30,1')->name('qr.designs.store');


// QR_LOGO_PRIVATE_STORAGE
/**
 * Logo yükleme. Giriş (auth) ve dakikada 20 istek sınırı vardır; yönetici kontrolü denetleyicidedir.
 */
Route::post('/qr/logos', [
    \App\Http\Controllers\QrLogoController::class, 'upload'
])->middleware(['auth', 'throttle:20,1'])
  ->name('qr.logos.upload');

/**
 * Yüklenmiş logoyu gösterir. Giriş (auth) ve dakikada 120 istek sınırı vardır.
 */
Route::get('/qr/logos/{filename}', [
    \App\Http\Controllers\QrLogoController::class, 'show'
])->middleware(['auth', 'throttle:120,1'])
  // Dosya adı yalnızca UUID + png/jpg/jpeg biçiminde kabul edilir. Bu, ../ gibi yol manipülasyonunu engeller.
  ->where('filename', '[0-9a-fA-F-]{36}\.(png|jpg|jpeg)')
  ->name('qr.logos.show');

// GOOGLE_REVIEW_GENERATOR
/**
 * Google yorum (değerlendirme) sayfası. Giriş istemez; arama kısmı ayrıca korunur.
 */
Route::get('/google-yorum', [
    \App\Http\Controllers\GoogleReviewController::class, 'index'
])->name('google.review.index');

// GOOGLE_PLACES_SEARCH
/**
 * Google Places'ta işletme arar. Giriş (auth) ve dakikada 10 istek sınırı vardır; yönetici kontrolü denetleyicidedir.
 */
Route::post('/google-yorum/ara', [
    \App\Http\Controllers\GoogleReviewController::class, 'search'
])->middleware(['auth', 'throttle:10,1'])
  ->name('google.review.search');

// INSTAGRAM_FOLLOW_GENERATOR
/**
 * Instagram takip bağlantısı üretici sayfası (yalnız görünüm döner, denetleyici yok). Giriş (auth) ve istek sınırı yoktur; herkese açıktır.
 */
Route::view('/instagram-takip', 'generators.instagram-follow')
    ->name('instagram.follow.index');

Route::view('/utm-builder', 'generators.utm-builder')
    ->name('utm.builder.index');

// LINK_PROJECT_STORAGE
/**
 * Bağlantı/NFC projesini kaydeder. Giriş (auth) ve dakikada 30 istek sınırı vardır; kayıt mantığı LinkProjectController@store içindedir.
 * Oturumsuz kullanıcı /admin/login adresine yönlendirilir (bootstrap/app.php redirectGuestsTo).
 */
Route::post('/baglantilar/kaydet', [
    \App\Http\Controllers\LinkProjectController::class, 'store'
])->middleware(['auth', 'throttle:30,1'])
  ->name('link.projects.store');



// NFC_STUDIO_ROUTES_V1
/**
 * NFC Studio sayfası. Giriş (auth) gerekir; yönetici kontrolü rota içinde yapılır (is_admin değilse 403).
 * Yalnız oturumdaki kullanıcının projelerini (id, name, type, target_url) en yeniden eskiye listeler. İstek sınırı yoktur.
 * Oturumsuz kullanıcı /admin/login adresine yönlendirilir (bootstrap/app.php redirectGuestsTo).
 */
Route::get('/nfc-studio', function (\Illuminate\Http\Request $request) {
    abort_unless($request->user()?->is_admin, 403);

    $projects = \App\Models\LinkProject::query()
        ->where('user_id', $request->user()->id)
        ->latest()
        ->get(['id', 'name', 'type', 'target_url']);

    $cards = \App\Models\NfcCard::query()
        ->where('user_id', $request->user()->id)
        ->whereIn('status', ['pending', 'failed', 'written'])
        ->whereNotNull('link_project_id')
        ->latest()
        ->get(['id', 'label', 'status', 'link_project_id']);

    return view('generators.nfc-studio', [
        'projects' => $projects,
        'cards' => $cards,
    ]);
})->middleware('auth')->name('nfc.studio');

Route::post('/nfc/cards/{card}/written', [
    \App\Http\Controllers\NfcCardWriteController::class, 'written',
])->middleware(['auth', 'throttle:30,1'])->name('nfc.cards.written');

Route::post('/nfc/cards/{card}/verified', [
    \App\Http\Controllers\NfcCardWriteController::class, 'verified',
])->middleware(['auth', 'throttle:30,1'])->name('nfc.cards.verified');
