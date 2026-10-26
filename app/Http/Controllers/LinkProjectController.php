<?php
// Bağlantı projesini (Google yorum / Instagram takip) doğrulayıp kaydeder (JSON). Sadece yönetici (is_admin) kullanabilir.
// Kim çağırır: routes/web.php ('link.projects.store' rotası). Kayıt: LinkProject modeli (link_projects tablosu).
// Hedef adres sıkı doğrulanır; yalnız beklenen alan adı ve yapıdaki bağlantılar kabul edilir.

namespace App\Http\Controllers;

use App\Models\LinkProject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Bağlantı projesi denetleyicisi: yeni bağlantıyı doğrular ve oturumdaki yöneticiye bağlı olarak kaydeder.
 */
class LinkProjectController extends Controller
{
    /**
     * Yeni bağlantı kaydı oluşturur (201). Aynı kullanıcı+tür+adres zaten varsa yenisini açmaz, mevcut id'yi döner.
     */
    public function store(Request $request): JsonResponse
    {
        abort_unless(
            $request->user() && $request->user()->is_admin,
            403
        );

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'type' => [
                'required',
                Rule::in(['google_review', 'instagram_follow']),
            ],
            'target_url' => [
                'required',
                'url',
                'max:2048',
            ],
        ]);

        // Adres parçalara ayrılır; ayrıştırılamayan değer baştan reddedilir.
        $url = parse_url($data['target_url']);

        if (!is_array($url)) {
            throw ValidationException::withMessages([
                'target_url' => 'Geçersiz bağlantı.',
            ]);
        }

        $scheme = strtolower($url['scheme'] ?? '');
        $host = strtolower($url['host'] ?? '');

        // Yalnız https: düz http, javascript: gibi şemalar ve adrese gömülü kimlik bilgisi (user/pass) istenmez.
        // Port ve fragment (#) reddedilir; böylece adres beklenmeyen bir porta ya da istemci tarafı yönlendirmeye çıkamaz.
        if (
            $scheme !== 'https' ||
            isset($url['user']) ||
            isset($url['pass']) ||
            isset($url['port']) ||
            isset($url['fragment'])
        ) {
            throw ValidationException::withMessages([
                'target_url' => 'Geçerli bir HTTPS bağlantısı girin.',
            ]);
        }

        // Sorgu dizesi dizi olarak okunur; placeid ve xtok bu diziden doğrulanır.
        parse_str($url['query'] ?? '', $params);

        $metadata = [];

        if ($data['type'] === 'google_review') {
            $placeId = $params['placeid'] ?? null;

            // Yalnızca Google'ın yorum yazma adresi kabul edilir; placeid biçimi doğrulanır ki rastgele metin metadata'ya girmesin.

            if (
                $host !== 'search.google.com' ||
                ($url['path'] ?? '') !== '/local/writereview' ||
                !is_string($placeId) ||
                !preg_match('/^[A-Za-z0-9_-]{10,200}$/', $placeId)
            ) {
                throw ValidationException::withMessages([
                    'target_url' => 'Geçerli Google yorum bağlantısı girin.',
                ]);
            }

            $metadata = [
                'place_id' => $placeId,
            ];
        }

        if ($data['type'] === 'instagram_follow') {
            $username = trim($url['path'] ?? '', '/');
            $token = $params['xtok'] ?? null;

            // Yol Instagram kullanıcı adı olmalı (harf, rakam, nokta, alt çizgi; en çok 30).
            // xtok paylaşım jetonu zorunludur ve güvenli karakterlerle sınırlıdır; eksik/bozuk bağlantı kaydedilmez.

            if (
                !in_array(
                    $host,
                    ['instagram.com', 'www.instagram.com'],
                    true
                ) ||
                !preg_match('/^[A-Za-z0-9._]{1,30}$/', $username) ||
                !is_string($token) ||
                !preg_match('/^[A-Za-z0-9_+\/=-]{8,256}$/', $token)
            ) {
                throw ValidationException::withMessages([
                    'target_url' => 'Geçerli Instagram paylaşım bağlantısı girin.',
                ]);
            }

            $metadata = [
                'username' => $username,
            ];
        }

        // Mükerrer kayıt önlenir: aynı kullanıcı için aynı tür ve adres bir kez saklanır.
        $existing = LinkProject::query()
            ->where('user_id', $request->user()->id)
            ->where('type', $data['type'])
            ->where('target_url', $data['target_url'])
            ->first();

        if ($existing) {
            return response()->json([
                'id' => $existing->id,
                'message' => 'Bu bağlantı zaten kayıtlı.',
                'already_exists' => true,
            ]);
        }

        $project = new LinkProject([
            'name' => $data['name'],
            'type' => $data['type'],
            'target_url' => $data['target_url'],
            'metadata' => $metadata,
        ]);

        // user_id $fillable içinde değildir (toplu atamayla başka kullanıcıya bağlanamasın); oturumdaki kullanıcıdan elle atanır.
        $project->user_id = $request->user()->id;
        $project->save();

        return response()->json([
            'id' => $project->id,
            'message' => 'Bağlantı başarıyla kaydedildi.',
            'already_exists' => false,
        ], 201);
    }
}
