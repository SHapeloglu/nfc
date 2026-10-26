<?php
// Filament yönetim panelindeki 'QR Tasarımları' sayfası. Oturumdaki yöneticinin kayıtlı QR tasarımlarını listeler.
// Adres: /admin/qr-tasarimlari. Görünüm dosyası: resources/views/filament/pages/qr-designs.blade.php.

namespace App\Filament\Pages;

use App\Models\QrDesign;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;

/**
 * Filament sayfası: menüde 'QR Tasarımları' olarak görünür. Sadece yöneticiler açabilir (canAccess).
 */
class QrDesigns extends Page
{
    protected static ?string $title = 'QR Tasarımları';

    protected static ?string $navigationLabel = 'QR Tasarımları';

    protected static ?string $slug = 'qr-tasarimlari';

    protected string $view = 'filament.pages.qr-designs';

    /**
     * Sayfayı kimin açabileceğini belirler: giriş yapmış ve is_admin=true olan kullanıcı. Filament bu metodu sayfa açılmadan önce çağırır.
     */
    public static function canAccess(): bool
    {
        return auth()->check()
            && (bool) auth()->user()->is_admin;
    }

    /**
     * Oturumdaki kullanıcının QR tasarımlarını, son güncellenen en üstte olacak şekilde en fazla 100 tane döner.
     * Yetkisiz çağrıda 403 hatası fırlatır.
     */
    public function getDesigns(): Collection
    {
        // Yetki kontrolü: sayfa metodu da çağrıldığı için ayrıca kontrol edilir (sadece görünümde gizlemek yetmez).
        abort_unless(static::canAccess(), 403);

        // Sorgu sadece oturumdaki kullanıcının kayıtlarını getirir (user_id filtresi); başkasının tasarımı görünmez.
        return QrDesign::query()
            ->where('user_id', auth()->id())
            ->latest('updated_at')
            ->limit(100)
            ->get();
    }
}
