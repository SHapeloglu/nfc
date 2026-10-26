<?php
// link_projects tablosunu oluşturur: kullanıcıların kayıtlı Google yorum / Instagram takip bağlantıları.
// Bağlı olduğu tablo: users (user_id). Kullanan yerler: LinkProject modeli, LinkProjectController, Filament kaynağı.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabloyu oluşturur. Kullanıcı silinirse bağlantıları da silinir (cascadeOnDelete).
     */
    public function up(): void
    {
        Schema::create('link_projects', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name', 150);

            $table->string('type', 50);

            $table->text('target_url');

            // Türe özel ek bilgi (place_id, username vb.); ilk sürümde boş olabilir.
            $table->jsonb('metadata')->nullable();

            $table->timestamps();

            // Kullanıcının bağlantılarını türe göre ve tarihe göre listelemek için.
            $table->index(['user_id', 'type']);

            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Tabloyu geri alır.
     */
    public function down(): void
    {
        Schema::dropIfExists('link_projects');
    }
};
