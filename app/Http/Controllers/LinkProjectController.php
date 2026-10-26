<?php
// Bağlantı projesini (Google yorum / Instagram takip) doğrulayıp kaydeder (JSON). Sadece yönetici (is_admin) kullanabilir.
// Kim çağırır: routes/web.php ('link.projects.store' rotası). Kayıt: LinkProject modeli (link_projects tablosu).
// Hedef adres sıkı doğrulanır; yalnız beklenen alan adı ve yapıdaki bağlantılar kabul edilir.

namespace App\Http\Controllers;

use App\Models\LinkProject;
use App\Services\LinkTargetService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Bağlantı projesi denetleyicisi: yeni bağlantıyı doğrular ve oturumdaki yöneticiye bağlı olarak kaydeder.
 */
class LinkProjectController extends Controller
{
    /**
     * Yeni bağlantı kaydı oluşturur (201). Aynı kullanıcı+tür+adres zaten varsa yenisini açmaz, mevcut id'yi döner.
     */
    public function store(Request $request, LinkTargetService $linkTargets): JsonResponse
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

        $metadata = $linkTargets->metadataFor($data['type'], $data['target_url']);

        // Mükerrer kayıt önlenir: aynı kullanıcı için aynı tür ve adres bir kez saklanır.
        $existing = LinkProject::query()
            ->where('user_id', $request->user()->id)
            ->where('type', $data['type'])
            ->where('target_url', $data['target_url'])
            ->first();

        if ($existing) {
            return $this->alreadyExistsResponse($existing);
        }

        $project = new LinkProject([
            'name' => $data['name'],
            'type' => $data['type'],
            'target_url' => $data['target_url'],
            'metadata' => $metadata,
        ]);

        // user_id $fillable içinde değildir (toplu atamayla başka kullanıcıya bağlanamasın); oturumdaki kullanıcıdan elle atanır.
        $project->user_id = $request->user()->id;
        try {
            // Kayıt kendi (savepoint) işleminde yapılır: PostgreSQL'de benzersizlik hatası dıştaki işlemi iptal etmesin, kazananı bulan sorgu çalışabilsin.
            DB::transaction(fn () => $project->save());
        } catch (UniqueConstraintViolationException $exception) {
            // Eşzamanlı iki istekten kaybeden: kazananın kaydı 'zaten kayıtlı' olarak döner.
            $winner = LinkProject::query()
                ->where('user_id', $request->user()->id)
                ->where('type', $data['type'])
                ->where('url_hash', hash('sha256', $data['target_url']))
                ->first();

            if (! $winner) {
                throw $exception;
            }

            return $this->alreadyExistsResponse($winner);
        }

        return response()->json([
            'id' => $project->id,
            'message' => 'Bağlantı başarıyla kaydedildi.',
            'already_exists' => false,
        ], 201);
    }

    /**
     * Aynı bağlantı zaten kayıtlıysa dönen cevap (200, already_exists=true).
     */
    private function alreadyExistsResponse(LinkProject $project): JsonResponse
    {
        return response()->json([
            'id' => $project->id,
            'message' => 'Bu bağlantı zaten kayıtlı.',
            'already_exists' => true,
        ]);
    }
}
