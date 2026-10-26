<?php
// Filament yönetim paneli ayarları: adres (/admin), giriş, renk, sayfa/kaynak keşfi ve ara katmanlar.
// bootstrap/providers.php ile yüklenir. Panele girişte User::canAccessPanel() kuralı da geçerlidir.

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Filament yönetim paneli sağlayıcısı. Panelin adresi, girişi, rengi, sayfaları ve ara katmanları burada ayarlanır.
 */
class AdminPanelProvider extends PanelProvider
{
    /**
     * Paneli oluşturur. Ayarlar zincirleme (fluent) yöntemlerle verilir; her yöntem paneli bir özellikle yapılandırır.
     */
    public function panel(Panel $panel): Panel
    {
        return $panel
            // Bu panel varsayılan paneldir (adres belirtilmeden açılan ilk panel).
            ->default()
            ->id('admin')
            ->path('admin')
            // Giriş ekranı etkin: giriş yapmamış kullanıcı /admin/login'e yönlendirilir.
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            // Kaynaklar (Resources), sayfalar (Pages) ve widget'lar ilgili klasörde otomatik bulunur. Yeni sayfa için o klasöre dosya koymak yeterlidir.
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            // Ara katmanlar sırayla çalışır: çerez, oturum, CSRF koruması (PreventRequestForgery) ve Filament olay kaydı.
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            // Girişi zorunlu kılan ara katman: giriş yapmamış kullanıcı login ekranına yönlendirilir.
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
