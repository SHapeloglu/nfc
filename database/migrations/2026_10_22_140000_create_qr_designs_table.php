<?php
// qr_designs tablosunu oluşturur: her kullanıcının QR tasarımları.
// Bağlı olduğu tablo: users (user_id). Migration sırası: add_is_admin migration'ı bu tarihten önce çalışır.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Tabloyu oluşturur. Her tasarım bir kullanıcıya bağlıdır; kullanıcı silinirse tasarımları da silinir (cascadeOnDelete).
     */
    public function up(): void
    {
        Schema::create('qr_designs', function (Blueprint $table) {
            $table->id();
            // user_id: tasarımın sahibi. users tablosuna yabancı anahtar olarak bağlanır.
            $table->foreignId('user_id')
                ->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            // settings: QR ayarlarının tamamı (içerik, renk, nokta tipi, logo vb.) JSON olarak saklanır.
            $table->jsonb('settings');
            $table->timestamps();

            // user_id ve updated_at birlikte indekslenir: bir kullanıcının tasarımlarını son güncellemeye göre listelemek hızlı olsun diye.
            $table->index(['user_id', 'updated_at']);
        });
    }

    /**
     * Tabloyu siler. Geri alma sırasında tüm tasarım kayıtları gider; dikkatli kullanın.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_designs');
    }
};
