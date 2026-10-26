<?php
// QR kodunun ortasına konacak logoyu yükler ve gösterir. Sadece yönetici kullanabilir.
// Dosyalar herkese açık klasöre değil, kullanıcıya ait özel klasöre kaydedilir.
// Kim çağırır: routes/web.php (/qr/logos). Yüklenen logo, QrDesignController ile tasarıma bağlanır.

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;

/**
 * QR logo denetleyicisi: logo yükleme ve güvenli gösterme işlemlerini yapar.
 */
class QrLogoController extends Controller
{
    /**
     * Yönetici kontrolü: kullanıcı yoksa veya is_admin false ise 403 ile durur. 'Yönetici girişi gerekli' mesajı döner.
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
     * Logoyu yükler. Sadece PNG/JPG (en çok 1 MB) kabul edilir.
     * Cevap: dosya adı, gösterim adresi ve mesaj. Kaydedilemezse 500 döner.
     */
    public function upload(Request $request)
    {
        $this->authorizeAdmin($request);

        // Dosya kontrolü: resim olmalı, uzantısı png/jpg/jpeg olmalı ve en çok 1 MB olmalı.
        $request->validate([
            'logo' => [
                'required',
                'image',
                File::types(['png', 'jpg', 'jpeg'])->max('1mb'),
            ],
        ]);

        $file = $request->file('logo');
        $extension = $file->extension();

        // Uzantı ayrıca kontrol edilir (dosyanın algılanan türüne göre). Uygun değilse 422 döner.
        abort_unless(
            in_array($extension, ['png', 'jpg', 'jpeg'], true),
            422
        );

        // Dosya adı rastgele UUID olur: kullanıcı dosya adını seçemez; çakışma ve yol saldırısı (path traversal) engellenir.
        $filename = Str::uuid()->toString() . '.' . $extension;
        // Klasör kullanıcı kimliğine göre ayrılır: her kullanıcı yalnızca kendi logolarına erişir.
        $directory = 'qr-logos/' . $request->user()->id;

        // Dosya 'local' disk üzerine kaydedilir. Bu disk herkese açık değildir; dosyalara sadece bu denetleyici üzerinden erişilir.
        $stored = Storage::disk('local')->putFileAs(
            $directory,
            $file,
            $filename
        );

        // Kayıt başarısızsa 500 döner.
        abort_unless($stored !== false, 500);

        return response()->json([
            'filename' => $filename,
            'url' => route('qr.logos.show', [
                'filename' => $filename
            ]),
            'message' => 'Logo yuklendi.'
        ]);
    }

    /**
     * Yüklenmiş logoyu tarayıcıya gönderir. Sadece dosyanın sahibi olan yönetici görebilir (yol kullanıcı kimliğiyle kurulur).
     */
    public function show(Request $request, string $filename)
    {
        $this->authorizeAdmin($request);

        // Dosya adı formatı kontrol edilir (UUID + uzantı). Uymuyorsa 404 döner; bu hem yol saldırısını hem rastgele dosya aramasını engeller.
        abort_unless(
            preg_match('/^[0-9a-f-]{36}\.(png|jpg|jpeg)$/', $filename),
            404
        );

        // Dosya yolu yalnızca oturumdaki kullanıcının klasörüne göre kurulur; başka kullanıcının dosyasına ulaşılamaz.
        $path = 'qr-logos/' . $request->user()->id
              . '/' . $filename;

        // Dosya yoksa 404 döner.
        abort_unless(
            Storage::disk('local')->exists($path),
            404
        );

        // Dosya tarayıcıya gönderilir. private, no-store: ara bellekte saklanmaz.
        $response = response()->file(
            Storage::disk('local')->path($path),
            [
                'X-Content-Type-Options' => 'nosniff',
            ]
        );

        $response->setPrivate();
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
