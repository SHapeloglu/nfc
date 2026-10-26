<?php
// Kayıtlı bağlantının modeli (link_projects tablosu). Her kayıt bir kullanıcıya aittir (user_id).
// Türe özel ek bilgiler (place_id, username) 'metadata' alanında JSON olarak saklanır.
// Kullanıldığı yer: LinkProjectController ve Filament 'Bağlantı Yönetimi' kaynağı.

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bağlantı projesi kaydı. user_id, sahip olan kullanıcıyı tutar; bu alan $fillable içinde değildir ve kod tarafında elle atanır.
 */
class LinkProject extends Model
{
    protected $fillable = [
        'name',
        'type',
        'target_url',
        'metadata',
    ];

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
}
