<?php

// NFC köprüsü için yönetici kullanıcının projelerini JSON olarak döner.
// Kim çağırır: routes/api.php (GET /api/nfc/projects, rota adı api.nfc.projects).

namespace App\Http\Controllers;

use App\Models\LinkProject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * NFC proje API denetleyicisi: jeton yetkisi ve yönetici kontrolünden sonra proje listesini verir.
 */
class NfcProjectController extends Controller
{
    /**
     * Yönetici değilse veya jeton gerçek bir PersonalAccessToken değilse / 'nfc:read' yetkisi yoksa 403 verir.
     * Cevap: {success, count, projects}.
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

        $projects = LinkProject::query()
            ->where('user_id', $user->id)
            ->latest()
            ->get(['id', 'name', 'type', 'target_url']);

        return response()->json([
            'success' => true,
            'count' => $projects->count(),
            'projects' => $projects,
        ]);
    }
}
