<?php
// QR tasarımlarını listeler ve kaydeder (JSON). Sadece yönetici (is_admin) kullanabilir.
// Kim çağırır: routes/web.php (/qr/designs). Ayarlar ayrıntılı doğrulanır; logolar başka kullanıcıya bağlanamaz.

namespace App\Http\Controllers;

use App\Models\QrDesign;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

/**
 * QR tasarımı denetleyicisi: kullanıcının tasarımlarını listeler ve kaydeder. Tüm işlemler yönetici girişi gerektirir.
 */
class QrDesignController extends Controller
{
    /**
     * Yönetici kontrolü: kullanıcı yoksa veya is_admin false ise 403 ile durur.
     */
    private function authorizeAdmin(Request $request): void
    {
        abort_unless(
            $request->user() && $request->user()->is_admin,
            403,
            'Yonetici girisi gerekli.'
        );
    }

    /**
     * Oturumdaki kullanıcının tasarımlarını (id, ad, ayarlar, güncelleme zamanı) en fazla 100 tane JSON olarak döner.
     */
    public function index(Request $request)
    {
        $this->authorizeAdmin($request);

        return response()->json(
            QrDesign::where('user_id', $request->user()->id)
                ->orderByDesc('updated_at')
                ->limit(100)
                ->get(['id', 'name', 'settings', 'updated_at'])
        );
    }

    /**
     * Tasarımı kaydeder. 'id' gelmezse yeni kayıt, gelirse o kaydın güncellenmesi yapılır.
     * Hata durumunda doğrulama hatası (422) veya bulunamadı (404) döner.
     */
    public function store(Request $request)
    {
        $this->authorizeAdmin($request);

        // Ayarlar 'settings' içinde gelir ve her alan ayrı ayrı doğrulanır. İzin verilmeyen değer (ör. bilinmeyen nokta tipi) 422 ile reddedilir.
        $data = $request->validate([
            'id' => ['nullable', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:150'],
            'settings' => ['required', 'array'],
            'settings.content' => ['required', 'string', 'max:2000'],
            'settings.dots' => [
                'required', Rule::in([
                    'square', 'rounded', 'dots',
                    'classy', 'classy-rounded', 'extra-rounded'
                ])
            ],
            'settings.corners' => [
                'required', Rule::in(['square', 'extra-rounded', 'dot'])
            ],
            'settings.fg' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'settings.bg' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'settings.cornerColor' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'settings.gradient' => [
                'required', Rule::in(['none', 'linear', 'radial'])
            ],
            'settings.gradientEnd' => [
                'required', 'regex:/^#[0-9a-fA-F]{6}$/'
            ],
            'settings.size' => [
                'required', Rule::in(['300', '500', '800', '1000', '2000'])
            ],
            'settings.margin' => [
                'required', Rule::in(['10', '20', '40', '60'])
            ],
            'settings.logoFile' => [
                'nullable',
                'string',
                'regex:/^[0-9a-f-]{36}\\.(png|jpg|jpeg)$/'
            ],
            'settings.logoSize' => [
                'nullable', 'integer', 'between:10,30'
            ],
            'settings.logoMargin' => [
                'nullable', 'integer', Rule::in([0,3,5,8])
            ],
        ]);

        // Sadece izin verilen alanlar kaydedilir; fazladan gönderilen alanlar atılır. Böylece beklenmeyen veri veritabanına girmez.
        $settings = collect($data['settings'])->only([
            'content', 'dots', 'corners', 'fg', 'bg',
            'cornerColor', 'gradient', 'gradientEnd',
            'size', 'margin',
            'logoFile', 'logoSize', 'logoMargin'
        ])->all();

        // Logo varsa dosya kullanıcının kendi klasöründe var mı kontrol edilir. Yol kullanıcı kimliğiyle kurulduğu için başka kullanıcının logosuna bağlanamaz.
        if (!empty($settings['logoFile'])) {
            $logoPath = 'qr-logos/' . $request->user()->id
                . '/' . $settings['logoFile'];

            abort_unless(
                Storage::disk('local')->exists($logoPath),
                422,
                'Logo dosyasi bulunamadi.'
            );
        }

        // Güncelleme: findOrFail ile sadece kendi tasarımı bulunur. Başkasının kaydı için 404 döner (yetki kontrolü).
        if (!empty($data['id'])) {
            $design = QrDesign::where('user_id', $request->user()->id)
                ->findOrFail($data['id']);

            $design->update([
                'name' => $data['name'],
                'settings' => $settings,
            ]);
        } else {
            // Yeni kayıt: user_id elle atanır (toplu atamayla girilmez), böylece kullanıcı başkası adına kayıt yapamaz.
            $design = new QrDesign([
                'name' => $data['name'],
                'settings' => $settings,
            ]);
            $design->user_id = $request->user()->id;
            $design->save();
        }

        return response()->json([
            'id' => $design->id,
            'name' => $design->name,
            'message' => 'Tasarim kaydedildi.'
        ]);
    }
}
