# architect.md — Generator Studio (dev) Mimarisi

Laravel 13 + Filament 5 uygulaması; PHP-FPM ve nginx ayrı konteynerlerde, veritabanı ortak PostgreSQL sunucusu. Bu dosya `/opt/generator-studio-dev` geliştirme kopyasını anlatır (2026-10-27 durumu: NFC entegrasyonu dev çalışma ağacında, commit edilmemiş).

## Bileşenler

```
Geliştirici / curl
  │  http://127.0.0.1:18087   (host nginx sitesi yok; yalnız yerel)
  ▼
generator-studio-dev-web  (nginx:stable-alpine, kod :ro bağlı)
  │  fastcgi_pass app:9000   (ağ: internal)
  ▼
generator-studio-dev-app  (php:8.4-fpm-bookworm, kod ./ → /var/www/html)
  │  ağ: internal + ortak-db
  ▼
postgres-ortak  (ayrı geliştirme veritabanı; canlıdan farklı)
```

Canlı kopya aynı yapıdadır: `generator-studio-web` (`127.0.0.1:18085`) ← host nginx (`/etc/nginx/sites-available/nfc-generator`) ← `https://nfc.carimatik.com.tr`. Canlı, dev'in 2026-10-27 durumundan geridedir (NFC envanteri ve WiFi/UTM yok; bkz. `task.md`).

## Uygulama katmanları

- **Rotalar:** `routes/web.php` (web), `routes/api.php` (`/api`, Sanctum), `/up` sağlık kontrolü. Closure rota kalmadı; `php artisan route:cache` çalışır.
- **Denetleyiciler** (`app/Http/Controllers/`):
  - `QrGeneratorController`: PNG/SVG üretimi (`endroid/qr-code`, hata düzeltme High); kapasite aşımı doğrulama hatası (`Data too big` → 422).
  - `QrDesignController`: tasarım listeleme/kaydetme; ayarlar beyaz listeyle doğrulanır.
  - `QrLogoController`: logo yükleme (PNG/JPG, en çok 1 MB) ve gösterme; `local` disk, `qr-logos/{user_id}/{uuid}.ext`; yanıt `private, no-store`.
  - `WifiQrController`: WiFi QR (`/wifi-qr`, `/wifi-qr/generate`); SSID/parola `TrimStrings` istisnası (`bootstrap/app.php`).
  - `GoogleReviewController` → `GooglePlacesService` (Places `searchText`, 15 dk önbellek).
  - `LinkProjectController`: Google yorum / Instagram takip bağlantısı kaydı; katı URL doğrulaması; `LinkTargetService` ile hedef doğrulama; unique indeks ihlalinde mevcut kaydı döner.
  - `NfcStudioController`, `NfcProjectController`, `CurrentUserController`: eskiden route closure'ıydı; artık `route:cache` uyumlu.
  - `NfcCardWriteController`: kart `written` / `verified` geçişleri; `lockForUpdate()` sonrası proje yeniden doğrulanır; URL karşılaştırması iki tarafta normalize edilir (`canonicalUrl`).
- **Servisler** (`app/Services/`): `LinkTargetService` (bağlantı hedefi doğrulama tek yerde), `GooglePlacesService`.
- **Modeller:** `User` (`is_admin`, `FilamentUser`, `HasApiTokens`), `QrDesign` (`settings` jsonb), `LinkProject` (`metadata` jsonb; `url_hash`; `nfcCards()`), `Business` (işletme), `NfcCard` (`status`: pending/written/verified/failed/delivered; `tag_uid`; `business`, `link_project`).
- **Filament** (`/admin`, `AdminPanelProvider`): `QrDesigns` sayfası (`/admin/qr-tasarimlari`), `LinkProjects` kaynağı, `Businesses` ve `NfcCards` kaynakları (`NfcCardResource`: "Yeniden hazırla" eylemi). Panele yalnız `is_admin` kullanıcı girer.
- **Görünümler** (`resources/views/generators/`): `qr`, `wifi-qr`, `utm-builder`, `google-review`, `instagram-follow`, `nfc-studio`; ana sayfa `welcome` (WiFi/UTM kartları ve NFC Studio bağlantısı).
- **İstemci kodu:** `public/vendor/qr-styling` (QR stil kütüphanesi), `public/js/link-save.js`. NFC Studio yazma/okuma akışı tarayıcı NFC API'si ister (Android Chrome); çift `reading` olayı `handled` bayrağıyla engellenir.

## Rota ve yetki özeti

| Rota | Koruma |
|---|---|
| `GET /`, `/qr`, `/google-yorum`, `/instagram-takip`, `/utm-builder` | Herkese açık |
| `GET /wifi-qr`, `POST /wifi-qr/generate` | Herkese açık; üretim 30/dk |
| `POST /qr/generate` | Herkese açık, 30/dk |
| `GET/POST /qr/designs` | Giriş middleware'i yok; `is_admin` denetleyicide (403) |
| `POST /qr/logos`, `GET /qr/logos/{filename}` | `auth` + `is_admin` |
| `POST /google-yorum/ara` | `auth` + `is_admin`, 10/dk |
| `POST /baglantilar/kaydet` | `auth` + `is_admin` (denetleyicide), 30/dk |
| `GET /nfc-studio` | `auth` + `is_admin` (rota içinde, 403) |
| `POST /nfc/cards/{card}/written`, `/verified` | `auth` + `is_admin` (denetleyicide, 403), 30/dk |
| `GET /api/nfc/projects` | `auth:sanctum` + token yetkisi `nfc:read` + `is_admin`, 30/dk |

Yetki modeli tek bayraktır: `users.is_admin`. Kullanıcıya ait tüm sorgular `user_id` ile sınırlanır.

## Veri

- Tablolar: `users` (+`is_admin`), `cache`, `jobs`, `qr_designs`, `link_projects`, `personal_access_tokens`, `businesses`, `nfc_cards`.
- `qr_designs`, `link_projects`, `businesses`, `nfc_cards`: `user_id` yabancı anahtar (`cascadeOnDelete`), `(user_id, …)` indeksleri.
- `link_projects.url_hash` (sha256(target_url), model `saving` olayıyla atanır) ve `unique(user_id, type, url_hash)`: tekrar kaydı veritabanı düzeyinde engeller.
- `nfc_cards`: `business_id` `restrictOnDelete` (işletme, bağlı kart varken silinemez); `link_project_id` `nullOnDelete`; `unique(user_id, tag_uid)` görünüyor (2026-10-27 00:23 değişikliği; kullanıcı kararı bekliyor, bkz. `task.md`).
- Migration durumu (2026-10-27): dev'de 10 migration dosyası (canlıda 7; canlıya geçişte 3 bekliyor: url_hash, businesses, nfc_cards). Dev DB'de 10 migration uygulanmış.

## Çalışma ortamı kararları

- Dev: `APP_ENV=local`, `APP_DEBUG=true`. Canlıda HTTPS `AppServiceProvider` ile `production` ortamında zorlanır.
- Kuyruk `sync`, önbellek `file`, oturum `file`, mail `log`.
- Kod bağlama: app'te yazılabilir, nginx'te salt okunur.
- Git: dev `main` dalında; NFC işleri çalışma ağacında commit'siz (`origin/feature/integration` d8e7590 ile aynı içerik). Canlı klasör git deposu değil; değişiklikler yedek sonekleri ve dosya diff'iyle izlenir.
- Hata cevapları: `api/*` ve JSON bekleyen istekler JSON döner; oturumsuz `auth` web isteği `/admin/login`'e yönlendirilir (`bootstrap/app.php`, `redirectGuestsTo`; canlıda bu kural yok).

## Dağıtım

- `compose.yaml` (`name: generator-studio-dev`), imaj `generator-studio-php:8.4`, Dockerfile: `docker/php/Dockerfile` (pdo_pgsql, zip, intl, gd, bcmath, opcache; Composer resmi imajdan).
- nginx: `docker/nginx/default.conf` (`root /var/www/html/public`, `client_max_body_size 20M`, nokta ile başlayan yollar engelli).
- Ağlar: `internal` (özel köprü), `ortak-db` (external, önceden var olmalı).
- Sağlık kontrolü: `app` → `php -v`; `web` → `wget http://127.0.0.1/up` (nginx → FPM → Laravel zinciri).
- Testler: SQLite bellek (`phpunit.xml`); dev PostgreSQL'e dokunmaz. Sayı 2026-10-27 uzlaştırma notunda 228 (SQLite ve PostgreSQL 16); commit öncesi `php artisan test` ile yeniden sayılmalı.
- Canlıya geçiş adımları: `/root/yedekler/is-analiz-20261026/rapor/NFC-254-255.md` §5.
