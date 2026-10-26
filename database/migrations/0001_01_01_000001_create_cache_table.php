<?php
// Veritabanı tabanlı önbellek (cache) ve kilit (lock) tabloları. CACHE_STORE=database ise kullanılır.
// up() tabloları oluşturur; down() siler. Çalışmış migration değiştirilmez.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // cache: anahtar-değer önbelleği. expiration sütunu kaydın geçerlilik süresini (unix zamanı) tutar.
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->bigInteger('expiration')->index();
        });

        // cache_locks: aynı işin aynı anda iki kez çalışmasını engelleyen kilit tablosu.
        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->bigInteger('expiration')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
