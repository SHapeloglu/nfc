<?php

namespace App\Http\Controllers;

use App\Models\LinkProject;
use App\Models\NfcCard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Stores an operator-reported browser NFC write and its subsequent read-back.
 * Browser reports are not cryptographic proof that a physical tag was written.
 */
class NfcCardWriteController extends Controller
{
    public function written(Request $request, NfcCard $card): JsonResponse
    {
        $this->authorizeCard($request, $card);

        $data = $request->validate([
            'project_id' => ['required', 'integer', 'min:1'],
            'written_url' => ['required', 'url', 'max:2048'],
        ]);

        $this->validateProject($request, $card, (int) $data['project_id'], $data['written_url']);

        DB::transaction(function () use ($card, $data): void {
            $lockedCard = NfcCard::query()->whereKey($card->id)->lockForUpdate()->firstOrFail();

            if (!in_array($lockedCard->status, ['pending', 'failed', 'written'], true)) {
                throw ValidationException::withMessages([
                    'card' => 'Teslim edilmiş veya doğrulanmış kart yeniden yazılamaz.',
                ]);
            }

            $lockedCard->forceFill([
                'status' => 'written',
                'written_url' => $data['written_url'],
                'written_at' => now(),
                'verified_at' => null,
            ])->save();
        });

        return response()->json(['status' => 'written', 'message' => 'Tarayıcı yazma sonucunu bildirdi. Kartı yeniden okutun.']);
    }

    public function verified(Request $request, NfcCard $card): JsonResponse
    {
        $this->authorizeCard($request, $card);

        $data = $request->validate([
            'project_id' => ['required', 'integer', 'min:1'],
            'verified_url' => ['required', 'url', 'max:2048'],
        ]);

        $this->validateProject($request, $card, (int) $data['project_id'], $data['verified_url']);

        DB::transaction(function () use ($card, $data): void {
            $lockedCard = NfcCard::query()->whereKey($card->id)->lockForUpdate()->firstOrFail();

            if ($lockedCard->status !== 'written' || $lockedCard->written_url !== $data['verified_url']) {
                throw ValidationException::withMessages([
                    'card' => 'Kart yazma kaydı ile yeniden okunan bağlantı uyuşmuyor.',
                ]);
            }

            $lockedCard->forceFill([
                'status' => 'verified',
                'verified_at' => now(),
            ])->save();
        });

        return response()->json(['status' => 'verified', 'message' => 'Tarayıcıdan bildirilen yeniden okuma sonucu kaydedildi.']);
    }

    private function authorizeCard(Request $request, NfcCard $card): void
    {
        abort_unless($request->user()?->is_admin, 403);
        abort_unless($card->user_id === $request->user()->id, 404);
    }

    private function validateProject(Request $request, NfcCard $card, int $projectId, string $url): void
    {
        $project = LinkProject::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($projectId);

        if ($card->link_project_id !== $project->id || $url !== $project->target_url) {
            throw ValidationException::withMessages([
                'project_id' => 'Kartın bağlantı projesi veya hedef adresi uyuşmuyor.',
            ]);
        }
    }
}
