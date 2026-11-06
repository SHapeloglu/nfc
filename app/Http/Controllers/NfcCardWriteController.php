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

        DB::transaction(function () use ($request, $card, $data): void {
            $lockedCard = NfcCard::query()->whereKey($card->id)->lockForUpdate()->firstOrFail();

            // Proje ve kart ilişkisi kilit içinde yeniden doğrulanır: kart bu arada başka projeye bağlanmış olabilir.
            $this->validateProject($request, $lockedCard, (int) $data['project_id'], $data['written_url']);

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

        DB::transaction(function () use ($request, $card, $data): void {
            $lockedCard = NfcCard::query()->whereKey($card->id)->lockForUpdate()->firstOrFail();

            // Proje ve kart ilişkisi kilit içinde yeniden doğrulanır (bkz. written).
            $this->validateProject($request, $lockedCard, (int) $data['project_id'], $data['verified_url']);

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

        // Hem kayıtlı adres hem istekteki adres aynı kurala göre normalize edilerek karşılaştırılır.
        if ((int) $card->link_project_id !== $project->id
            || $this->canonicalUrl($url) !== $this->canonicalUrl($project->target_url)) {
            throw ValidationException::withMessages([
                'project_id' => 'Kartın bağlantı projesi veya hedef adresi uyuşmuyor.',
            ]);
        }
    }

    /**
     * Tarayıcının new URL(adres).href çıktısına yakın, sınırlı bir normalizasyon:
     * şema ve host küçük harf, boş yol '/', varsayılan port kaldırılır,
     * boşluk ve bazı özel karakterler yüzde kodlanır. Sorgu ve parça korunur.
     * Ayrıştırılamayan adres olduğu gibi döner (karşılaştırma yine katı kalır).
     */
    private function canonicalUrl(string $url): string
    {
        $parts = parse_url($url);

        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            return $url;
        }

        $scheme = strtolower($parts['scheme']);
        $defaultPort = ['https' => 443, 'http' => 80][$scheme] ?? null;

        $canonical = $scheme.'://';

        if (isset($parts['user'])) {
            $canonical .= $parts['user'].(isset($parts['pass']) ? ':'.$parts['pass'] : '').'@';
        }

        $canonical .= strtolower($parts['host']);

        if (isset($parts['port']) && $parts['port'] !== $defaultPort) {
            $canonical .= ':'.$parts['port'];
        }

        $path = $parts['path'] ?? '';
        $canonical .= $this->percentEncode($path === '' ? '/' : $path, '/[\x00-\x20"<>`{}\x7F-\xFF]/');

        if (isset($parts['query'])) {
            $canonical .= '?'.$this->percentEncode($parts['query'], '/[\x00-\x20"#<>\x27\x7F-\xFF]/');
        }

        if (isset($parts['fragment'])) {
            $canonical .= '#'.$this->percentEncode($parts['fragment'], '/[\x00-\x20"<>`\x7F-\xFF]/');
        }

        return $canonical;
    }

    private function percentEncode(string $component, string $pattern): string
    {
        return preg_replace_callback($pattern, static fn (array $match): string => sprintf('%%%02X', ord($match[0])), $component);
    }
}
