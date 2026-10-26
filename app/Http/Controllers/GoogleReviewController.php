<?php
// Google yorum (değerlendirme) sayfası ve işletme arama API'si. Arama sadece yöneticiye açıktır.
// Kim çağırır: routes/web.php (/google-yorum ve /google-yorum/ara).
// Bağlı olduğu: GooglePlacesService.

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\GooglePlacesService;
use RuntimeException;

/**
 * Google yorum denetleyicisi. Sayfayı gösterir ve Google Places'ta arama yapar.
 */
class GoogleReviewController extends Controller
{
    /**
     * Google yorum sayfasını (generators.google-review görünümü) gösterir.
     */
    public function index(): View
    {
        return view('generators.google-review');
    }

    /**
     * Google Places'ta işletme arar. Sadece yönetici kullanabilir.
     * Cevap: results listesi (en fazla 10). Google hatasında 502 ve genel bir mesaj döner.
     */
    public function search(
        Request $request,
        GooglePlacesService $places
    ): JsonResponse {
        // Sadece yönetici (is_admin) arama yapabilir; değilse 403 döner.
        abort_unless(
            $request->user() && $request->user()->is_admin,
            403
        );

        // Sorgu 3-120 karakter olmalı; aksi halde doğrulama hatası döner.
        $data = $request->validate([
            'query' => ['required', 'string', 'min:3', 'max:120'],
        ]);

        // Servis hataları (RuntimeException) burada yakalanır; hata loglanır ve 502 döner. Ayrıntı kullanıcıya gösterilmez.
        try {
            return response()->json([
                'results' => $places->search($data['query']),
            ]);
        } catch (RuntimeException $e) {
            report($e);

            return response()->json([
                'message' => 'Google isletme aramasi basarisiz.',
            ], 502);
        }
    }
}
