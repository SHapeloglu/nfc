<?php
// Uygulamanın yüklenen Service Provider listesi. AppServiceProvider ve Filament panel sağlayıcısı burada kayıtlıdır.

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
];
