<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Oturumdaki kullanıcıyı JSON döner. Sanctum kimlik doğrulaması (auth:sanctum) gerekir; istek sınırı yoktur.
 * Kimliksiz istek 401 alır (JSON cevabı; bootstrap/app.php).
 */
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
// NFC_BRIDGE_PROJECTS_API_V1
/**
 * NFC köprüsü için yönetici kullanıcının projelerini JSON döner (GET /api/nfc/projects).
 * Sanctum (auth:sanctum) ve dakikada 30 istek sınırı vardır. Yönetici kontrolü (is_admin) rota içinde yapılır;
 * ayrıca jetonun 'nfc:read' yetkisi olmalıdır, yoksa 403. Kimliksiz istek 401 alır.
 */
Route::get('/nfc/projects', function (Request $request) {

    $user = $request->user();

    abort_unless($user && $user->is_admin, 403);

    $token = $user->currentAccessToken();

    abort_unless(
        $token instanceof \Laravel\Sanctum\PersonalAccessToken
        && $token->can('nfc:read'),
        403
    );

    $projects = \App\Models\LinkProject::query()
        ->where('user_id', $user->id)
        ->latest()
        ->get([
            'id',
            'name',
            'type',
            'target_url',
        ]);

    return response()->json([
        'success' => true,
        'count' => $projects->count(),
        'projects' => $projects,
    ]);

})->middleware([
    'auth:sanctum',
    'throttle:30,1',
])->name('api.nfc.projects');
