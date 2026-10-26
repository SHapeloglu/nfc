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

### Route closure'ları denetleyiciye taşınmalı
- **Kategori:** teknik borç
- **Neden:** `/nfc-studio` ve `/api/nfc/projects` `routes/*.php` içinde closure; yetki ve sorgu mantığı rotada. Closure'lı rotalar `route:cache` ile uyumsuzdur.
- **Notlar:** Denetleyici + Form Request ile diğer uçlarla aynı yapıya getirilebilir.

### Tutarlı yetki yardımcısı
- **Kategori:** teknik borç
- **Neden:** `abort_unless(... is_admin ...)` beş yerde tekrar ediyor; `QrDesignController` ve `QrLogoController` ayrı `authorizeAdmin` metoduna sahip.
- **Notlar:** Middleware (`admin`) veya Gate ile tek yerde toplanabilir; oturumsuz yönlendirme sorunuyla (`task.md`) birlikte ele alınmalı.

### Doğrulama kuralları tekrarı
- **Kategori:** teknik borç
- **Neden:** Logo dosya adı regex'i route'ta (büyük harf serbest) ve denetleyicilerde (yalnız küçük harf) farklı.
- **Notlar:** Tek sabit/kural nesnesinde toplanmalı.

### `link_projects` indeks ve saklama
- **Kategori:** teknik borç
- **Neden:** `target_url` `text`; tekrar kontrolü için unique indeks yok.
- **Notlar:** URL özeti (hash) sütunuyla `(user_id, type, url_hash)` unique indeksi mümkün.

### Kod tabanındaki yedek dosyaları
- **Kategori:** teknik borç
- **Neden:** `*.backup-*`, `*.before-*`, `*.bak*` dosyaları uygulama ağacında; karışıklık ve yanlışlıkla yüklenme riski.
- **Notlar:** Git kurulunca kaldırılabilir.

### Yorum ve stil tutarlılığı
- **Kategori:** teknik borç
- **Neden:** Eski dosyalar yoğun Türkçe yorumlu, `LinkProject*` dosyaları yorumsuz.
- **Notlar:** `vendor/bin/pint` çalıştırılmadı; PHP dosyaları değiştirilirse çalıştırılmalı.

### Konteyner sağlık kontrolü
- **Kategori:** iyileştirme
- **Neden:** `app` sağlık kontrolü `php -v`; uygulama (HTTP) düzeyinde değil. `web` konteynerinde kontrol yok.
- **Notlar:** `/up` ucuna `curl` tabanlı kontrol eklenebilir.

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
