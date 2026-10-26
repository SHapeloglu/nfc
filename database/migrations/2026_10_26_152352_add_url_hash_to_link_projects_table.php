<?php

// link_projects tablosuna url_hash (sha256(target_url)) ekler ve (user_id, type, url_hash) üzerinde benzersiz indeks kurar.
// Amaç: eşzamanlı isteklerde aynı kullanıcı+tür+adres için çift kayıt oluşmasını veritabanı düzeyinde engellemek.
// target_url 'text' olduğundan doğrudan unique indeks yerine sabit uzunluklu özet kullanılır.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sütunu ekler, mevcut satırları doldurur, sonra benzersiz indeksi kurar.
     * Mevcut tabloda tekrar eden satır varsa indeks adımı hata verir; satırlar silinmez/birleştirilmez.
     */
    public function up(): void
    {
        Schema::table('link_projects', function (Blueprint $table) {
            $table->char('url_hash', 64)->nullable();
        });

        DB::table('link_projects')
            ->select(['id', 'target_url'])
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('link_projects')
                        ->where('id', $row->id)
                        ->update(['url_hash' => hash('sha256', $row->target_url)]);
                }
            });

        Schema::table('link_projects', function (Blueprint $table) {
            $table->unique(['user_id', 'type', 'url_hash']);
        });
    }

    /**
     * Benzersiz indeksi ve sütunu kaldırır.
     */
    public function down(): void
    {
        Schema::table('link_projects', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'type', 'url_hash']);
        });

        Schema::table('link_projects', function (Blueprint $table) {
            $table->dropColumn('url_hash');
        });
    }
};
