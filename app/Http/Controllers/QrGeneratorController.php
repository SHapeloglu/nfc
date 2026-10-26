<?php
// QR kodu üreticisi: sayfayı gösterir ve QR kodunu (PNG veya SVG) anında üretip geri döner. Kayıt yapmaz.
// Kim çağırır: routes/web.php (/qr ve /qr/generate). Giriş istemez; istek sınırı (throttle) vardır.
// Kütüphane: endroid/qr-code.

namespace App\Http\Controllers;

use BaconQrCode\Exception\WriterException;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * QR üretim denetleyicisi.
 */
class QrGeneratorController extends Controller
{
    /**
     * QR üretici sayfasını (generators.qr görünümü) gösterir.
     */
    public function index()
    {
        return view('generators.qr');
    }

    /**
     * Form verisiyle QR kodu üretir ve dosya olarak döner.
     * Girdiler sunucuda doğrulanır. Hata durumunda Laravel 422 cevabı döner.
     */
    public function generate(Request $request)
    {
        // Girdiler: içerik en fazla 2000 karakter; boyut 200-1000 piksel; renkler #RRGGBB biçiminde; format png veya svg.
        $data = $request->validate([
            'content' => ['required', 'string', 'max:2000'],
            'size' => ['required', 'integer', 'between:200,1000'],
            'foreground' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'background' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'format' => ['required', Rule::in(['png', 'svg'])],
        ]);

        // Onaltılık renk kodunu (#RRGGBB) RGB sayılarına çevirir: ilk iki karakter kırmızı, sonraki iki yeşil, son iki mavi (16'lık sistem).
        $color = function (string $hex): Color {
            return new Color(
                hexdec(substr($hex, 1, 2)),
                hexdec(substr($hex, 3, 2)),
                hexdec(substr($hex, 5, 2)),
            );
        };

        // QR kodu oluşturulur. High hata düzeltme: kodun yaklaşık %30'u bozulsa bile okunabilir; bu sayede ortasına logo konabilir. margin 20: kenar boşluğu (piksel).
        $qr = new QrCode(
            data: $data['content'],
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: (int) $data['size'],
            margin: 20,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: $color($data['foreground']),
            backgroundColor: $color($data['background']),
        );

        // Format seçimi: SVG (vektör) veya PNG (görüntü).
        $writer = $data['format'] === 'svg'
            ? new SvgWriter()
            : new PngWriter();

        // QR kapasitesi aşılırsa doğrulama hatası döndür.
        try {
            $result = $writer->write($qr);
        } catch (WriterException $exception) {
            if (!str_contains($exception->getMessage(), 'Data too big')) {
                throw $exception;
            }

            throw ValidationException::withMessages([
                'content' => 'İçerik QR kod kapasitesini aşıyor. Lütfen daha kısa bir metin veya bağlantı kullanın.',
            ]);
        }

        // Cevap doğrudan dosyadır. no-store: tarayıcı ve ara sunucular saklamaz (içerik hassas olabilir); nosniff: tarayıcı içerik türünü tahmin etmez.
        return response($result->getString(), 200, [
            'Content-Type' => $result->getMimeType(),
            'Content-Disposition' => 'inline; filename="generator-studio-qr.'.$data['format'].'"',
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
