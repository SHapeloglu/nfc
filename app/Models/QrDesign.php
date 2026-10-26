<?php

// Kayıtlı QR tasarımının modeli (qr_designs tablosu). Her tasarım bir kullanıcıya aittir (user_id).
// Ayarlar (renk, nokta tipi, logo vb.) 'settings' alanında JSON olarak saklanır.
// Kullanıldığı yer: QrDesignController ve Filament 'QR Tasarımları' sayfası.

namespace App\Models;

use Database\Factories\QrDesignFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * QR tasarımı kaydı. user_id, sahip olan kullanıcıyı tutar; bu alan $fillable içinde değildir ve denetleyicide elle atanır.
 */
class QrDesign extends Model
{
    /** @use HasFactory<QrDesignFactory> */
    use HasFactory;

    protected $fillable = ['name', 'settings'];

    /**
     * Hangi alanın nasıl çevrileceğini söyler: 'settings' veritabanında JSON metin, PHP'de dizi olarak kullanılır.
     */
    protected function casts(): array
    {
        return ['settings' => 'array'];
    }

    /**
     * Tasarımın sahibi olan kullanıcı.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
