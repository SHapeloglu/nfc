<?php
// Uygulamanın Service Provider'ı. boot() içinde canlı ortamda HTTPS zorlaması yapılır.
// bootstrap/providers.php ile yüklenir. Servis kayıtları (register) şu an boştur.

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Laravel'in Service Provider yapısı: uygulama servislerini kaydeder ve başlatır.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Canlı ortamda (production) tüm bağlantılar HTTPS'e yönlendirilir. Yerel geliştirmede bu ayar etkisizdir.
        if (app()->environment('production')) {
            \Illuminate\Support\Facades\URL::forceHttps();
        }
    }
}
