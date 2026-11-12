# architect.md — Generator Studio (dev) Mimarisi

Laravel 13 + Filament 5 uygulaması; PHP-FPM ve nginx ayrı konteynerlerde, veritabanı ortak PostgreSQL sunucusu. Bu dosya `/opt/generator-studio-dev` geliştirme kopyasını anlatır (son kontrol: 2026-11-11).

## Bileşenler

```
Geliştirici / curl
  │  http://127.0.0.1:18087   (host nginx: generator-studio-dev.powerbi.com.tr → 18087)
  ▼
generator-studio-dev-web  (nginx:stable-alpine, kod :ro bağlı)
  │  fastcgi_pass app:9000   (ağ: internal)
  ▼
generator-studio-dev-app  (php:8.4-fpm-bookworm, kod ./ → /var/www/html)
  │  ağ: internal + ortak-db
  ▼
postgres-ortak  (ayrı geliştirme veritabanı; canlıdan farklı)
```

Canlı kopya aynı yapıdadır: `generator-studio-web` (`127.0.0.1:18085`) ← host nginx (`/etc/nginx/sites-available/nfc-generator`) ← `https://nfc.carimatik.com.tr`. Canlı bir git deposu değildir.

## Dış istemciler (bu repoda değil)

NFC kartlarına yazmak için iki istemci, Sanctum jetonu ile sunucudaki API'yi kullanır:

- **Windows masaüstü uygulaması** (`C:\Users\yeliz\nfc-pc`, `NfcYazici.exe`): ACR122U'ya `winscard.dll` üzerinden `ctypes` ile bağlanır. Yerel köprü (`nfc_kopru.py`, `127.0.0.1:8765`) ve `nfckopru:` protokolü de bu klasördedir.
- **Android uygulaması** (`~/nfc-android`, APK): ACR122U'ya USB host (CCID) ile bağlanır. `NdefCodec`, `NtagCard` ve `NfcApi` sınıflarını içerir.

Her iki istemci de `GET /api/nfc/cards` ile kartları listeler, yazma sonrası `POST …/written` ve `POST …/verified` ile durumu bildirir. Mod seçimi (bağlantı ve kart / serbest) istemci tarafındadır; serbest modda sunucuya hiçbir şey bildirilmez.

## Uygulama katmanları

- **Rotalar:** `routes/web.php` (web), `routes/api.php` (`/api`, Sanctum), `/up` sağlık kontrolü.
- **Denetleyiciler** (`app/Http/Controllers/`):
  - `QrGeneratorController`: PNG/SVG üretimi (`endroid/qr-code`, hata düzeltme High), kayıt yok.
  - `QrDesignController`: tasarım listeleme/kaydetme; ayarlar beyaz listeyle doğrulanır.
  - `QrLogoController`: logo yükleme (PNG/JPG, en çok 1 MB) ve gösterme; `local` disk, `qr-logos/{user_id}/{uuid}.ext`; yanıt `private, no-store`.
  - `WifiQrController`: Wi-Fi QR üretimi (`/wifi-qr`).
  - `GoogleReviewController` → `GooglePlacesService` (Places `searchText`, 15 dk önbellek).
  - `LinkProjectController`: Google yorum / Instagram takip bağlantısı kaydı; katı URL doğrulaması; `LinkTargetService` hedef URL'yi üretir.
  - `NfcStudioController`: `/nfc-studio` sayfası.
  - `NfcProjectController`: `GET /api/nfc/projects`.
  - `NfcCardListController`: `GET /api/nfc/cards?proje=ID`; yalnız `pending`, `failed`, `written` kartları döner.
  - `NfcCardWriteController`: `written` ve `verified` bildirimleri (web oturumu ve API jetonu için ortak). URL, kartın bağlı olduğu `LinkProject.target_url` ile birebir eşleşmeli.
  - `CurrentUserController`: `GET /api/user`.
- **Modeller:** `User` (`is_admin`, `FilamentUser`, `HasApiTokens`), `QrDesign` (`settings` jsonb), `LinkProject` (`metadata` jsonb, `url_hash`), `Business` (işletme), `NfcCard` (`status`, `written_at`, `verified_at`, `delivered_at`; `status` ve zaman damgaları mass assignment dışında, yalnız kod tarafından yazılır).
- **Servisler** (`app/Services/`): `GooglePlacesService`, `LinkTargetService`.
- **Filament** (`/admin`, `AdminPanelProvider`): `QrDesigns` sayfası (`/admin/qr-tasarimlari`), `LinkProjects`, `Businesses`, `NfcCards` kaynakları. Panele yalnız `is_admin` kullanıcı girer.
- **Görünümler** (`resources/views/generators/`): `qr`, `wifi-qr`, `google-review`, `instagram-follow`, `utm-builder`, `nfc-studio`; ana sayfa `welcome`.
- **İstemci kodu:** `public/vendor/qr-styling` (QR stil kütüphanesi), `public/js/link-save.js`. `nfc-studio` sayfasında PC köprüsü bölümü ve `nfckopru:` protokol düğmesi vardır (`127.0.0.1:8765`, izinli köken `https://nfc.carimatik.com.tr`).

## Rota ve yetki özeti

| Rota | Koruma |
|---|---|
| `GET /`, `/qr`, `/wifi-qr`, `/google-yorum`, `/instagram-takip`, `/utm-builder` | Herkese açık |
| `POST /qr/generate`, `POST /wifi-qr/generate` | Herkese açık, 30/dk |
| `GET/POST /qr/designs` | Giriş middleware'i yok; `is_admin` denetleyicide (403) |
| `POST /qr/logos`, `GET /qr/logos/{filename}` | `auth` + `is_admin` |
| `POST /google-yorum/ara` | `auth` + `is_admin`, 10/dk |
| `POST /baglantilar/kaydet` | `auth` + `is_admin`, 30/dk |
| `GET /nfc-studio` | `auth` + `is_admin` |
| `POST /nfc/cards/{card}/written\|verified` | `auth` + `is_admin` (denetleyicide), 30/dk |
| `GET /api/nfc/projects` | `auth:sanctum` + token yetkisi `nfc:read` + `is_admin`, 30/dk |
| `GET /api/nfc/cards` | `auth:sanctum` + `nfc:read` + `is_admin`, 30/dk |
| `POST /api/nfc/cards/{card}/written\|verified` | `auth:sanctum` + `nfc:write` (`CheckForAnyAbility`) + `is_admin`, 30/dk |

Yetki modeli tek bayraktır: `users.is_admin`. Kullanıcıya ait tüm sorgular `user_id` ile sınırlanır. Oturumsuz `auth` isteği `/admin/login`'e yönlendirilir; JSON/API isteği 401 alır.

## Veri

- Tablolar: `users` (+`is_admin`), `cache`, `jobs`, `qr_designs`, `link_projects` (+`url_hash`), `personal_access_tokens`, `businesses`, `nfc_cards`.
- `qr_designs`, `link_projects`, `businesses`, `nfc_cards`: `user_id` yabancı anahtar (`cascadeOnDelete`).
- Benzersizlik: `link_projects` için `(user_id, type, url_hash)`, `nfc_cards` için `(user_id, tag_uid)`. Boş UID `NULL` saklanır, böylece benzersiz indeks boş değerleri çakıştırmaz.
- Migration durumu (2026-11-11): dev'de 10 migration çalışmış. Canlıda aynı 10 migration uygulanmış.

## Çalışma ortamı kararları

- Dev: `APP_ENV=local`, `APP_DEBUG=true`. Canlıda HTTPS `AppServiceProvider` ile `production` ortamında zorlanır.
- Kuyruk `sync`, önbellek `file`, oturum `file`, mail `log`.
- Kod bağlama: app'te yazılabilir, nginx'te salt okunur. Git deposu var (`feature/integration` dalı); canlı dizin git değildir, yedekler `*.backup-*` soneki ile tutulur.
- Hata cevapları: `api/*` ve JSON bekleyen istekler JSON döner (`bootstrap/app.php`).

## Dağıtım

- `compose.yaml` (`name: generator-studio-dev`), imaj `generator-studio-php:8.4`, Dockerfile: `docker/php/Dockerfile` (pdo_pgsql, zip, intl, gd, bcmath, opcache; Composer resmi imajdan).
- nginx: `docker/nginx/default.conf` (`root /var/www/html/public`, `client_max_body_size 20M`, nokta ile başlayan yollar engelli).
- Ağlar: `internal` (özel köprü), `ortak-db` (external, önceden var olmalı).
- Sağlık kontrolü: `app` için `php -v` (uygulama düzeyinde değil); `web` için `wget /up`.
