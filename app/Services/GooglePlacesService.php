<?php
// Google Places (Text Search) API ile işletme arar. Sonuçlar 15 dakika önbelleğe alınır.
// Kim çağırır: GoogleReviewController::search.
// API anahtarı config/services.php üzerinden .env dosyasından gelir; koda yazılmaz.

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Google Places üzerinden işletme arayan servis. Önbellek ve sınır kontrolleri burada yapılır.
 */
class GooglePlacesService
{
    /**
     * Metin ile işletme arar.
     * @param string $query Aranacak metin (3-120 karakter)
     * @return array En fazla 10 sonuç: place_id, name, address.
     * Hata: geçersiz metin, eksik anahtar veya Google hatası RuntimeException fırlatır.
     */
    public function search(string $query): array
    {
        // Arama metninin başındaki ve sonundaki boşlukları temizle.
        $query = trim($query);

        // Metin 3-120 karakter olmalı. Kısa veya uzun sorgu Google'a gitmeden reddedilir (hem maliyet hem kötüye kullanım için).
        if (mb_strlen($query) < 3 || mb_strlen($query) > 120) {
            throw new RuntimeException('Gecersiz arama metni.');
        }

        // API anahtarı .env dosyasından gelir. Yoksa istek atılmaz ve açık bir hata verilir.
        $key = config('services.google_places.key');

        if (!$key) {
            throw new RuntimeException('Google API anahtari bulunamadi.');
        }

        // Önbellek anahtarı: sorgunun küçük harfli SHA-256 özeti. Aynı arama 15 dakika içinde tekrar Google'a gitmez (maliyet tasarrufu).
        $cacheKey = 'google-places:' . hash(
            'sha256',
            mb_strtolower($query)
        );

        // remember: önbellekte sonuç varsa kapanış (closure) çalışmaz; yoksa çalışır ve sonuç 15 dakika saklanır. Hata fırlarsa sonuç önbelleğe yazılmaz.
        return Cache::remember(
            $cacheKey,
            now()->addMinutes(15),
            function () use ($query, $key) {
                // Google'dan 12 saniye içinde yanıt beklenir; daha geç gelirse zaman aşımı oluşur.
                $response = Http::timeout(12)
                    ->withHeaders([
                        'X-Goog-Api-Key' => $key,
                        'X-Goog-FieldMask' =>
                            'places.id,places.displayName,places.formattedAddress',
                    ])
                    // Places API 'searchText' (metin araması) çağrısı. Dil Türkçe; alan maskesi sadece id, isim ve adres ister.
                    ->post(
                        'https://places.googleapis.com/v1/places:searchText',
                        [
                            'textQuery' => $query,
                            'languageCode' => 'tr',
                        ]
                    );

                // Google hata dönerse istisna fırlatılır. Bu durumda sonuç önbelleğe yazılmaz; GoogleReviewController bunu 502 cevabına çevirir.
                if (!$response->successful()) {
                    throw new RuntimeException(
                        'Google Places HTTP ' . $response->status()
                    );
                }

                // Sonuçlar temizlenir: id'si veya adı olmayan kayıtlar elenir, en fazla 10 tane alınır ve sade alanlarla döner.
                return collect($response->json('places', []))
                    ->filter(fn ($place) =>
                        !empty($place['id']) &&
                        !empty($place['displayName']['text'])
                    )
                    ->take(10)
                    ->map(fn ($place) => [
                        'place_id' => $place['id'],
                        'name' => $place['displayName']['text'],
                        'address' => $place['formattedAddress'] ?? '',
                    ])
                    ->values()
                    ->all();
            }
        );
    }
}
