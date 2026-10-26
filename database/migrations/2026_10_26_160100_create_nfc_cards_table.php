<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nfc_cards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->foreignId('link_project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label', 150);
            $table->string('status', 30)->default('pending');
            $table->string('tag_uid', 128)->nullable();
            $table->text('written_url')->nullable();
            $table->timestamp('written_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'business_id']);
            // Aynı kullanıcıda aynı UID tekrar edemez; NULL değerler birbirini engellemez.
            $table->unique(['user_id', 'tag_uid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nfc_cards');
    }
};
