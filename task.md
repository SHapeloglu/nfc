# task.md — Generator Studio (dev) Görevler

Bu dosya `/opt/generator-studio-dev` içindir. 2026-10-26 incelemesiyle yenilendi; önceki sürüm `task.md.backup-20261026-113902`.

## 🔜 Sıradaki

- [ ] **`/qr/generate` içerik uzunluğunda 500 (testle doğrulandı)** — doğrulama `content` için 2000 karaktere izin veriyor, ama hata düzeltme seviyesi High olduğundan yaklaşık 1273+ karakterde `endroid/qr-code` "Data too big" fırlatıyor ve istek 500 ile bitiyor (1200 karakter çalışıyor). Karar: `max`'ı ~1200'e indirmek veya hatayı 422'ye çevirmek. Davranışı kilitleyen test bilerek yazılmadı.
- [ ] **Logo yanıtında `Cache-Control: no-store, public` (testle doğrulandı)** — `QrLogoController::show` `private, no-store` gönderiyor ama `response()->file()` yanıtı `public` işaretliyor ve `private`'ı eziyor. `no-store` korunduğu için risk düşük; yine de `->setPrivate()` eklenmeli. Test yalnız `no-store`'u doğruluyor.
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

- [x] 2026-10-26 — Test paketi yazıldı: 121 test, 377 doğrulama, ~6 sn (SQLite bellek). Kapsam: QR üretimi (sınırlar, 429), tasarım ve logo uçları (403/404/422, başka kullanıcının kaydı), Google arama ve Places servisi (önbellek, hata, `Http::fake`), `LinkProject` URL doğrulaması, `/nfc-studio`, Sanctum API (`nfc:read`), `canAccessPanel`, Filament sayfaları, herkese açık sayfalar. Mutasyon denemesi: yönlendirme düzeltmesi geri alınınca 6 test kırıldı. Fabrikalar: `UserFactory::admin()`, `QrDesignFactory`, `LinkProjectFactory`; modellere `HasFactory` ve `QrDesign::user()` eklendi.
- [x] 2026-10-26 — Port çakışması giderildi: dev web `18086` → `18087` (`compose.yaml`, konteyner yeniden oluşturuldu). `18086` artık boş, `/opt/ipad1vnc` için serbest; canlı `18085` etkilenmedi.
- [x] 2026-10-26 — Oturumsuz `auth` isteğinde 500 giderildi: `bootstrap/app.php` içinde `redirectGuestsTo` ile `/admin/login`'e yönlendirme. Doğrulama: `/nfc-studio` ve `/qr/logos/…` 302 → `/admin/login`; JSON isteği 401; `/api/*` 401.
- [x] 2026-10-26 — Proje incelemesi; altı md dosyası dev kopyasına göre yenilendi.
- [x] 2026-10-26 — Doğrulama: Google yorum arama ucu `auth` + `throttle:10,1` + `is_admin` ile korunuyor (eski kayıtta "görünmüyor" deniyordu).
- [x] 2026-10-25 — `link_projects` ve `personal_access_tokens` migration'ları, Sanctum ve NFC API.
- [x] 2026-10-22 — `users.is_admin` ve `qr_designs` migration'ları; QR üretici, logo yükleme ve tasarım kaydı rotaları.
