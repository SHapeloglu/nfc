# backlog.md — Generator Studio (dev) Fikirler ve Teknik Borç

Son güncelleme: 2026-11-11. Planlanmış işler `task.md` içinde.

## Özellik fikirleri

### Tasarım silme ve yönetimi
- **Kategori:** yeni özellik
- **Neden:** `/qr/designs` yalnız listeler ve kaydeder; silme/yeniden adlandırma ucu yok.
- **Notlar:** `QrDesign` modelinde `user()` ilişkisi de yok. Aynı `is_admin` + `user_id` kalıbı uygulanmalı.

### Kullanıcı başına tasarım ve logo alanı
- **Kategori:** iyileştirme
- **Neden:** Tasarımlar yalnız yönetici tarafından kaydedilebiliyor.
- **Notlar:** Yetki modeli değişeceği için tüm `is_admin` kontrolleri (denetleyiciler, route closure'ları, Filament) gözden geçirilmeli. Tek bayrak yerine policy düşünülebilir.

### Kuyruk ve arka plan işleri
- **Kategori:** iyileştirme
- **Neden:** `QUEUE_CONNECTION=sync`; ağır işler istek içinde çalışır.
- **Notlar:** Worker konteyneri ve `database` veya Redis kuyruğu gerekir. Mevcut yük için acil değil.

### Gerçek e-posta gönderimi
- **Kategori:** yeni özellik
- **Neden:** `MAIL_MAILER=log`; bildirim yok.
- **Notlar:** SMTP ayarları `.env`'de; değerler md'ye yazılmaz.

### Kart yazma istemcilerinin tek kaynağa alınması
- **Kategori:** iyileştirme
- **Neden:** Windows (`nfc-pc`) ve Android (`nfc-android`) istemcileri aynı NTAG/NDEF mantığını iki kez yazıyor; mod ve URL doğrulama kuralları ayrı ayrı bakım istiyor.
- **Notlar:** Önce gerçek cihaz testleri bitsin. Ortak kural: URL `LinkProject.target_url` ile eşleşmeli; serbest mod sunucuya bildirim yapmaz.

### Kart envanteri raporu
- **Kategori:** yeni özellik
- **Neden:** Kart durumu (`pending`, `written`, `verified`, `delivered`) Filament tablosunda var ama işletme bazında özet yok.
- **Notlar:** `delivered_at` alanı var; teslim akışı henüz yok.

## Teknik borç

### Route closure'ları denetleyiciye taşınmalı (kısmen tamam)
- **Kategori:** teknik borç
- **Neden:** `/nfc-studio` ve `/api/nfc/projects` artık denetleyicilerde (`NfcStudioController`, `NfcProjectController`). `routes/api.php` closure içermiyor. `routes/web.php` içinde tek closure kaldı: ana sayfa `/` closure'ı.
- **Notlar:** Ana sayfa closure'ı da taşınabilir; ardından `route:cache` denenebilir.

### Tutarlı yetki yardımcısı
- **Kategori:** teknik borç
- **Neden:** `abort_unless(... is_admin ...)` kontrolü çok yerde tekrar ediyor (QR, Google, bağlantı, NFC denetleyicileri ve API).
- **Notlar:** Middleware (`admin`) veya Gate ile tek yerde toplanabilir. Yetki modeli değişirse (aşağıdaki "kullanıcı başına alan") birlikte ele alınmalı.

### Doğrulama kuralları tekrarı
- **Kategori:** teknik borç
- **Neden:** Logo dosya adı regex'i route'ta (büyük harf serbest) ve denetleyicilerde (yalnız küçük harf) farklı.
- **Notlar:** Tek sabit/kural nesnesinde toplanmalı.

### QR içerik sınırı ile hata düzeltme seviyesi
- **Kategori:** teknik borç
- **Neden:** `content` en çok 2000 karakter kabul ediyor; hata düzeltme High iken ~1273 karakterden sonra kütüphane hata veriyor ve istek 500 dönüyor.
- **Notlar:** Sınır ~1200 yapılabilir ya da hata 422 olarak döndürülebilir. `task.md` maddesi.

### Kod tabanındaki yedek dosyaları
- **Kategori:** teknik borç
- **Neden:** `*.backup-*`, `*.before-*`, `*.bak*` dosyaları uygulama ağacında; karışıklık ve yanlışlıkla yüklenme riski.
- **Notlar:** Git kullanılıyor; yedekler `.gitignore`'a eklenebilir veya silinebilir (kullanıcı kararı).

### Yorum ve stil tutarlılığı
- **Kategori:** teknik borç
- **Neden:** Eski dosyalar yoğun Türkçe yorumlu, `LinkProject*` ve NFC dosyaları daha az yorumlu.
- **Notlar:** PHP dosyaları değiştirilince `vendor/bin/pint --dirty --format agent` çalıştırılmalı.

### Konteyner sağlık kontrolü
- **Kategori:** iyileştirme
- **Neden:** `app` sağlık kontrolü `php -v`; uygulama (HTTP) düzeyinde değil. `web` tarafında `/up` kontrolü var.
- **Notlar:** `app` için de HTTP veya PHP-FPM düzeyinde bir kontrol düşünülebilir.

### Dev ortamı dayanıklılığı
- **Kategori:** araştırma
- **Neden:** Dev, canlıyla aynı PostgreSQL sunucusunu (ayrı veritabanı) ve aynı host'u paylaşıyor. Dev artık herkese açık bir domain'den erişiliyor ve `APP_DEBUG=true` (`README.md`).
- **Notlar:** Dev için ayrı kullanıcı/yetki düşünülebilir. Erişim kısıtlama kararı `task.md` içinde.

## Ekleme Şablonu

```markdown
### Başlık
- **Kategori:** yeni özellik / iyileştirme / teknik borç / araştırma
- **Neden:** kısa gerekçe
- **Notlar:** büyüklük, bağımlılıklar, riskler
```
