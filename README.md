# Generator Studio (dev)

QR kod üretici (PNG/SVG), kayıtlı QR tasarımları ve logo yükleme (yalnız yönetici), Google yorum ve Instagram takip bağlantısı üretimi, NFC bağlantı projeleri yapan Laravel 13 uygulaması (PHP 8.4-FPM + nginx, Docker Compose, Filament 5 yönetim paneli).

Bu dizin **geliştirme kopyasıdır** (`/opt/generator-studio-dev`). Canlı kurulum `/opt/generator-studio` içindedir ve `https://nfc.carimatik.com.tr` adresinden yayındadır. Ayrıntılı teknik özet ve kurallar: `CLAUDE.md`, mimari: `architect.md`.

## Çalıştırma
```bash
cd /opt/generator-studio-dev && docker compose up -d      # app + web
docker exec generator-studio-dev-app php artisan migrate:status
docker exec generator-studio-dev-app php artisan route:list --except-vendor
docker logs --tail 100 generator-studio-dev-app
curl -i http://127.0.0.1:18087/up                          # sağlık kontrolü
```
- Konteynerler: `generator-studio-dev-app` (PHP-FPM), `generator-studio-dev-web` (nginx, `127.0.0.1:18087 → 80`). Host'ta PHP kurulu değil; artisan komutları konteynerde çalıştırılır.
- Veritabanı: ortak PostgreSQL (`postgres-ortak`, ağ `ortak-db`), canlıdan **ayrı** bir geliştirme veritabanı. `ortak-db` ağı önceden oluşturulmuş olmalıdır.
- Yapılandırma `.env` dosyasındadır (değerler repoya/md'ye yazılmaz). Google arama için `GOOGLE_PLACES_API_KEY` gerekir.

## Sayfalar ve uçlar
| Adres | Açıklama | Erişim |
|---|---|---|
| `/`, `/qr`, `/google-yorum`, `/instagram-takip` | Karşılama, QR üretici, Google yorum, Instagram takip | Herkese açık |
| `POST /qr/generate` | QR üretimi (30 istek/dk) | Herkese açık |
| `/qr/designs`, `/qr/logos` | Tasarım kaydı, logo yükleme | Yönetici (`users.is_admin`) |
| `/nfc-studio`, `POST /baglantilar/kaydet` | NFC bağlantı projeleri | Giriş + yönetici |
| `GET /api/nfc/projects` | Sanctum token (`nfc:read`) ile proje listesi | Yönetici token'ı |
| `/admin` | Filament paneli (QR Tasarımları, Bağlantı Projeleri) | Yönetici |

## Teknik özet
Laravel `^13.17`, Filament `^5`, Sanctum `^4`, `endroid/qr-code ^6.1`. Kuyruk `sync`, önbellek ve oturum `file`, mail `log`. Logolar `storage/app/private/qr-logos/{user_id}/` altında, kamuya açık değildir.

## Dikkat
- Git deposu `github.com/SHapeloglu/nfc` (public). Yerel yedekler `*.backup-YYYYMMDD-HHMMSS` sonekiyle tutulur ve `.gitignore` ile dışarıda bırakılır.
- Dev web portu `18087`, canlı `18085`. (`18086` `/opt/ipad1vnc` projesine aittir.)
- Testler: `docker exec generator-studio-dev-app php artisan test --compact` (SQLite bellek, dev veritabanına dokunmaz; 121 test).
