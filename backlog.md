# backlog.md — Generator Studio (dev) Fikirler ve Teknik Borç

2026-10-26 incelemesiyle yenilendi. Planlanmış işler `task.md` içinde.

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

## Teknik borç

### CI (GitHub Actions)
- **Kategori:** yeni özellik
- **Neden:** Repo GitHub'da ama testleri ve Pint'i çalıştıran bir iş akışı yok; 151 test yalnız elle çalışıyor.
- **Notlar:** `.github/workflows/tests.yml`: PHP 8.4, `composer install`, SQLite bellek ile `php artisan test`, `pint --test`. Yerelde denenemez; ilk çalıştırmada ortam eklentileri (intl, gd, pdo_sqlite) gerekir.

### Tutarlı yetki yardımcısı
- **Kategori:** teknik borç
- **Neden:** `abort_unless(... is_admin ...)` beş yerde tekrar ediyor; `QrDesignController` ve `QrLogoController` ayrı `authorizeAdmin` metoduna sahip.
- **Notlar:** Middleware (`admin`) ile toplanabilir; yönlendirme düzeltmesi tamam. 151 test bu refactor'u korur. Etkilenen dosyalar: `QrDesignController`, `QrLogoController`, `GoogleReviewController`, `LinkProjectController`, `NfcStudioController`, `NfcProjectController` — paralel işlerle çakışmaması için tek başına yapılmalı.

### Doğrulama kuralları tekrarı
- **Kategori:** teknik borç
- **Neden:** Logo dosya adı regex'i route'ta (büyük harf serbest) ve denetleyicilerde (yalnız küçük harf) farklı.
- **Notlar:** Tek sabit/kural nesnesinde toplanmalı.

### Yorum ve stil tutarlılığı
- **Kategori:** teknik borç
- **Neden:** Eski dosyalar yoğun Türkçe yorumlu, `LinkProject*` dosyaları yorumsuz.
- **Notlar:** `vendor/bin/pint` çalıştırılmadı; PHP dosyaları değiştirilirse çalıştırılmalı.

### Konteyner sağlık kontrolü
- **Kategori:** iyileştirme
- **Neden:** `web` artık `/up` ile uygulama düzeyinde kontrol ediliyor (2026-10-26); `app` (PHP-FPM) hâlâ yalnız `php -v`.
- **Notlar:** FPM'e HTTP ile bakılamaz; `app` için `php-fpm-healthcheck` veya `cgi-fcgi` ile `/ping` düşünülebilir. Düşük öncelik.

### Dev ortamı dayanıklılığı
- **Kategori:** araştırma
- **Neden:** Dev, canlıyla aynı PostgreSQL sunucusunu (ayrı veritabanı) ve aynı host'u paylaşıyor; `ortak-db` ağında hatalı bir `.env` canlıya bağlanabilir.
- **Notlar:** Dev için ayrı kullanıcı/yetki düşünülebilir.

## Ekleme Şablonu

```markdown
### Başlık
- **Kategori:** yeni özellik / iyileştirme / teknik borç / araştırma
- **Neden:** kısa gerekçe
- **Notlar:** büyüklük, bağımlılıklar, riskler
```
