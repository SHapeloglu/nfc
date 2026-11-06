<?php

// Kayıtlı bağlantının modeli (link_projects tablosu). Her kayıt bir kullanıcıya aittir (user_id).
// Türe özel ek bilgiler (place_id, username) 'metadata' alanında JSON olarak saklanır.
// Kullanıldığı yer: LinkProjectController ve Filament 'Bağlantı Yönetimi' kaynağı.

namespace App\Models;

use Database\Factories\LinkProjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Bağlantı projesi kaydı. user_id, sahip olan kullanıcıyı tutar; bu alan $fillable içinde değildir ve kod tarafında elle atanır.
 */
class LinkProject extends Model
{
    /** @use HasFactory<LinkProjectFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'target_url',
        'metadata',
    ];

    /**
     * Kaydedilirken adresin sha256 özeti url_hash alanına yazılır; (user_id, type, url_hash) benzersiz indeksi
     * aynı bağlantının eşzamanlı iki kez eklenmesini veritabanı düzeyinde engeller.
     */
    protected static function booted(): void
    {
        static::saving(function (LinkProject $project): void {
            $project->url_hash = hash('sha256', (string) $project->target_url);
        });
    }

    /**
     * 'metadata' veritabanında JSON, PHP'de dizi olarak kullanılır.
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    /**
     * Kaydın sahibi olan kullanıcı.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Bu bağlantıyı kullanan NFC kartları.
     */
    public function nfcCards(): HasMany
    {
        return $this->hasMany(NfcCard::class);
    }
}
