<?php

// NFC köprüsü için bir bağlantıya bağlı, henüz doğrulanmamış kartları JSON olarak döner.
// Kim çağırır: routes/api.php (GET /api/nfc/cards?proje=ID, rota adı api.nfc.cards.index).

namespace App\Http\Controllers;

use App\Models\NfcCard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Yönetici kullanıcının, verilen bağlantı projesine bağlı ve yazılabilir durumdaki kartlarını listeler.
 */
class NfcCardListController extends Controller
{
    /**
     * Yönetici değilse veya jeton 'nfc:read' yetkisine sahip değilse 403 verir.
     * Cevap: {success, count, cards}.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user && $user->is_admin, 403);

        $token = $user->currentAccessToken();

        abort_unless(
            $token instanceof PersonalAccessToken
            && $token->can('nfc:read'),
            403
        );

        $data = $request->validate([
            'proje' => ['required', 'integer', 'min:1'],
        ]);

        $cards = NfcCard::query()
            ->where('user_id', $user->id)
            ->where('link_project_id', $data['proje'])
            ->whereIn('status', ['pending', 'failed', 'written'])
            ->latest()
            ->get(['id', 'label', 'status', 'link_project_id']);

        return response()->json([
            'success' => true,
            'count' => $cards->count(),
            'cards' => $cards,
        ]);
    }
}
