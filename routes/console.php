<?php
// Artisan (komut satırı) komutları. Web rotalarıyla ilgisi yoktur; bootstrap/app.php içinde 'commands' olarak yüklenir.

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/**
 * 'inspire' komutu: rastgele bir motivasyon sözü yazar. Örnek/şablon amaçlıdır.
 */
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
