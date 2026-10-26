# task.md — Generator Studio (dev) Görevler

Bu dosya `/opt/generator-studio-dev` içindir. 2026-10-26 incelemesiyle yenilendi; önceki sürüm `task.md.backup-20261026-113902`.

## 🔜 Sıradaki

- [ ] **Port 18086 çakışması** — `/opt/ipad1vnc/docker-compose.yml` `127.0.0.1:18086` tanımlıyor; dev web şu an bu portu tutuyor, ipad1vnc yeniden başlarsa port alamaz. Dev için başka bir port seçilmeli (karar kullanıcıda). `compose.yaml` içindeki `TODO(kontrol)` yorumu da hâlâ 18085'ten söz ediyor, güncellenmeli.
- [ ] **Testler örnek seviyesinde** — yalnız `tests/*/ExampleTest.php` var. Öncelik: yetki sınırları (oturumsuz, `is_admin=false`, başka kullanıcının tasarımı → 404), `QrDesignController` doğrulaması, logo yükleme, `LinkProjectController` URL doğrulaması, `/api/nfc/projects` token yetkisi.
- [x] **Git deposu** — 2026-10-26: `git init` yapıldı, `main` dalı `github.com/SHapeloglu/nfc` (public) reposuna push edildi. Yedek dosyaları `.gitignore` ile dışarıda.
- [ ] **`.env` yedeği gitignore'da değil** — `.gitignore` yalnız `.env.backup` içeriyor; `.env.backup*` deseni eklenmeli (onay gerekir). Dosya içeriği okunmadı.
- [ ] **Yedek dosyaları dizinde** — `routes/web.php.backup-*` (3), `web.php.before-*` (3), `routes/api.php.before-*`, `composer.*.before-nfc-api`, `app/Models/User.php.before-*`, `app/Providers/AppServiceProvider.php.bak-*`, `resources/views/**/*.bak*`, `*.before-*`, `backups/`. Kod ağacı dışına taşınmalı veya silinmeli (kullanıcı kararı). `public/` içinde yedek dosya yok (2026-10-26 kontrolü).
- [ ] **Tekrar kayıt yarışı** — `link_projects` tablosunda `(user_id, type, target_url)` için unique kısıt yok; tekrar kontrolü yalnız uygulamada. Eşzamanlı iki istek çift kayıt üretebilir.
- [ ] **Boost talimatı** — `CLAUDE.md` sonundaki `<laravel-boost-guidelines>` bloğu korunuyor; `laravel/boost` `composer.json` `require-dev` içinde var. Kurulum/kaldırma kararı kullanıcıda.
- [ ] **Canlı/dev farkı** — `/opt/generator-studio` ile bu kopya arasında `app/` farkı gözlenmedi; `routes/`, `resources/`, `database/` karşılaştırılmadı. Dev→canlı aktarım yöntemi tanımlanmalı.

## 🚧 Devam Eden

- [ ] NFC/bağlantı projeleri (`LinkProject`, `/baglantilar/kaydet`, `/nfc-studio`, `/api/nfc/projects`) — 2026-10-25 tarihli yeni özellik; testi ve canlıya alınma durumu belirsiz.
- [ ] QR tasarım yönetimi (Filament `QrDesigns` + `/qr/designs`) — tamamlanma durumu netleştirilmeli.

## ✅ Tamamlanan

- [x] 2026-10-26 — Oturumsuz `auth` isteğinde 500 giderildi: `bootstrap/app.php` içinde `redirectGuestsTo` ile `/admin/login`'e yönlendirme. Doğrulama: `/nfc-studio` ve `/qr/logos/…` 302 → `/admin/login`; JSON isteği 401; `/api/*` 401.
- [x] 2026-10-26 — Proje incelemesi; altı md dosyası dev kopyasına göre yenilendi.
- [x] 2026-10-26 — Doğrulama: Google yorum arama ucu `auth` + `throttle:10,1` + `is_admin` ile korunuyor (eski kayıtta "görünmüyor" deniyordu).
- [x] 2026-10-25 — `link_projects` ve `personal_access_tokens` migration'ları, Sanctum ve NFC API.
- [x] 2026-10-22 — `users.is_admin` ve `qr_designs` migration'ları; QR üretici, logo yükleme ve tasarım kaydı rotaları.
