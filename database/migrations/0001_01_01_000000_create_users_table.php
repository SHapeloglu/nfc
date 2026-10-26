<?php
// Laravel'in standart kullanıcı tabloları: users, password_reset_tokens ve sessions.
// up() tabloları oluşturur, down() bunları siler. Dosya adı 0001_01_01 ile başladığı için en önce çalışır.
// DİKKAT: Bir migration veritabanında bir kez çalıştırıldıktan sonra değiştirilmez; değişiklik için yeni bir migration yazılır.

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
        // users: kullanıcı hesapları. E-posta benzersiz (unique) tutulur. is_admin sütunu ayrı bir migration ile eklenir.
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        // password_reset_tokens: şifre sıfırlama bağlantıları için geçici tablo.
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // sessions: veritabanı tabanlı oturumlar (SESSION_DRIVER=database ise kullanılır).
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
