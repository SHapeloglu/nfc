# architect.md — Generator Studio (dev) Mimarisi

Laravel 13 + Filament 5 uygulaması; PHP-FPM ve nginx ayrı konteynerlerde, veritabanı ortak PostgreSQL sunucusu. Bu dosya `/opt/generator-studio-dev` geliştirme kopyasını anlatır.

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

Canlı kopya aynı yapıdadır: `generator-studio-web` (`127.0.0.1:18085`) ← host nginx (`/etc/nginx/sites-available/nfc-generator`) ← `https://nfc.carimatik.com.tr`.

## Uygulama katmanları

- **Rotalar:** `routes/web.php` (web), `routes/api.php` (`/api`, Sanctum), `/up` sağlık kontrolü.
- **Denetleyiciler** (`app/Http/Controllers/`):
  - `QrGeneratorController`: PNG/SVG üretimi (`endroid/qr-code`, hata düzeltme High), kayıt yok.
  - `QrDesignController`: tasarım listeleme/kaydetme; ayarlar beyaz listeyle doğrulanır.
  - `QrLogoController`: logo yükleme (PNG/JPG, en çok 1 MB) ve gösterme; `local` disk, `qr-logos/{user_id}/{uuid}.ext`.
  - `GoogleReviewController` → `GooglePlacesService` (Places `searchText`, 15 dk önbellek).
  - `LinkProjectController`: Google yorum / Instagram takip bağlantısı kaydı; katı URL doğrulaması.
- **Modeller:** `User` (`is_admin`, `FilamentUser`, `HasApiTokens`), `QrDesign` (`settings` jsonb), `LinkProject` (`metadata` jsonb).
- **Filament** (`/admin`, `AdminPanelProvider`): `QrDesigns` sayfası (`/admin/qr-tasarimlari`) ve `LinkProjects` kaynağı. Panele yalnız `is_admin` kullanıcı girer.
- **Görünümler** (`resources/views/generators/`): `qr`, `google-review`, `instagram-follow`, `nfc-studio`; ana sayfa `welcome`.
- **İstemci kodu:** `public/vendor/qr-styling` (QR stil kütüphanesi), `public/js/link-save.js`.

## Rota ve yetki özeti

| Rota | Koruma |
|---|---|
| `GET /`, `/qr`, `/google-yorum`, `/instagram-takip` | Herkese açık |
| `POST /qr/generate` | Herkese açık, 30/dk |
| `GET/POST /qr/designs` | Giriş middleware'i yok; `is_admin` denetleyicide (403) |
| `POST /qr/logos`, `GET /qr/logos/{filename}` | `auth` + `is_admin` |
| `POST /google-yorum/ara` | `auth` + `is_admin`, 10/dk |
| `POST /baglantilar/kaydet` | `auth` + `is_admin`, 30/dk |
| `GET /nfc-studio` | `auth` + `is_admin` |
| `GET /api/nfc/projects` | `auth:sanctum` + token yetkisi `nfc:read` + `is_admin`, 30/dk |

Yetki modeli tek bayraktır: `users.is_admin`. Kullanıcıya ait tüm sorgular `user_id` ile sınırlanır.

## Veri

- Tablolar: `users` (+`is_admin`), `cache`, `jobs`, `qr_designs`, `link_projects`, `personal_access_tokens`.
- `qr_designs` ve `link_projects`: `user_id` yabancı anahtar (`cascadeOnDelete`), `(user_id, …)` indeksleri.
- Migration durumu (2026-10-26): 7 migration çalıştı, hepsi batch 1.

## Çalışma ortamı kararları

- Dev: `APP_ENV=local`, `APP_DEBUG=true`. Canlıda HTTPS `AppServiceProvider` ile `production` ortamında zorlanır.
- Kuyruk `sync`, önbellek `file`, oturum `file`, mail `log`.
- Kod bağlama: app'te yazılabilir, nginx'te salt okunur. Git yok; değişiklikler yedek sonekleriyle izlenir.
- Hata cevapları: `api/*` ve JSON bekleyen istekler JSON döner (`bootstrap/app.php`).

## Dağıtım

- `compose.yaml` (`name: generator-studio-dev`), imaj `generator-studio-php:8.4`, Dockerfile: `docker/php/Dockerfile` (pdo_pgsql, zip, intl, gd, bcmath, opcache; Composer resmi imajdan).
- nginx: `docker/nginx/default.conf` (`root /var/www/html/public`, `client_max_body_size 20M`, nokta ile başlayan yollar engelli).
- Ağlar: `internal` (özel köprü), `ortak-db` (external, önceden var olmalı).
- Sağlık kontrolü: `php -v` (uygulama düzeyinde değil).
