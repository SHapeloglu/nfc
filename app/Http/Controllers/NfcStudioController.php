<?php

namespace App\Http\Controllers;

use App\Models\LinkProject;
use App\Models\NfcCard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

// NFC Studio sayfası (GET /nfc-studio). Giriş (auth) gerekir; yönetici değilse 403.
// Yalnız oturumdaki kullanıcının projelerini ve yazılmaya hazır/yazılmış kartlarını listeler.
class NfcStudioController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()?->is_admin, 403);

        $projects = LinkProject::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get(['id', 'name', 'type', 'target_url']);

        // Yazma akışının seçebileceği kartlar: bağlantısı olan, sahibi bu kullanıcı olan kartlar.
        $cards = NfcCard::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['pending', 'failed', 'written'])
            ->whereNotNull('link_project_id')
            ->latest()
            ->get(['id', 'label', 'status', 'link_project_id']);

        return view('generators.nfc-studio', [
            'projects' => $projects,
            'cards' => $cards,
        ]);
    }
}
