<?php
// users tablosuna is_admin (yönetici bayrağı) sütunu ekler. Filament paneli ve QR yönetimi bu bayrağa bakar.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * is_admin sütununu ekler. Varsayılan false: yeni kullanıcılar yönetici değildir.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // is_admin: yönetici olup olmadığını tutar. Yönetici yapmak için bu alan veritabanında elle true yapılır.
            $table->boolean('is_admin')->default(false);
        });
    }

    /**
     * is_admin sütununu kaldırır (geri alma).
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
};
