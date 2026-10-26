<?php
// Laravel başlangıç dosyası: web rotaları, komutlar, /up sağlık kontrolü ve hata cevabı biçimini tanımlar.
// Her HTTP isteği ve artisan komutu önce bu dosyayı okur. Değişiklikten sonra yapılandırma önbelleği temizlenmelidir.

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

// Uygulamayı kurar; web rotalarını (routes/web.php) ve komutları (routes/console.php) bağlar.
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // Oturumsuz kullanıcı 'auth' korumalı bir sayfaya girerse Filament giriş ekranına (/admin/login) yönlendirilir.
    // Uygulamada 'login' adlı rota yoktur; bu ayar olmadan Laravel route('login') arar ve 500 hatası verir.
    // JSON/API istekleri yönlendirilmez, 401 alır (aşağıdaki hata ayarı).
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => route('filament.admin.auth.login'));
    })
    // Hata cevapları: JSON bekleyen istekler (api/* ya da expectsJson) hata sayfası yerine JSON cevabı alır.
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
