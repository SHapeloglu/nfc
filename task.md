# task.md — Generator Studio (dev) Görevler

Bu dosya `/opt/generator-studio-dev` içindir. Son güncelleme: 2026-11-11 (canlı ve dev durumu karşılaştırıldı).

## 🔜 Sıradaki

- [ ] **Dev erişimi herkese açık ve `APP_DEBUG=true`** — `README.md` "Erişim" bölümüne göre dev `https://generator-studio-dev.powerbi.com.tr` üzerinden açık. Hata ayrıntıları dışarıya görünebilir. Karar: erişimi kısıtlamak (basic auth, IP kısıtı veya VPN) ya da `APP_DEBUG=false`. Kullanıcı kararı.
- [ ] **NFC kart API ve kart yazma değişiklikleri commit edilmedi** — `feature/integration` dalında, `origin/feature/integration` ile eşit. Değişmemiş dosyalar: `routes/api.php` (`GET /api/nfc/cards`, `POST …/written|verified` uçları), `resources/views/generators/nfc-studio.blade.php` (PC köprüsü bölümü), `README.md`, untracked `app/Http/Controllers/NfcCardListController.php` ve `tests/Feature/Http/Controllers/NfcCardApiTest.php`. Canlıda aynı uçlar var; dev'deki start/stop düğmeleri canlıda yok. Commit ve PR kararı kullanıcıda.
- [ ] **Canlı ile dev arasındaki fark** — canlıda `bridgeStartButton` yok (dev'de var). Canlıya aktarım yalnız kullanıcı onayıyla yapılır.
- [ ] **Telefon ve PC istemcileri test edilecek** — Android APK (`nfc-yazici-usb-debug.apk`, USB host, ACR122U) ve Windows `NfcYazici.exe`: mod seçimi, token girişi, okuma ve yazma. İstemciler bu repoda değil (`~/nfc-android`, `C:\Users\yeliz\nfc-pc`).
- [ ] **PR #1 (`feature/integration`)** — CLAUDE.md'ye göre gerçek Android+NTAG testinden sonra Ready/merge edilecek.
- [ ] **`/qr/generate` içerik uzunluğunda 500** — doğrulama `content` için `max:2000` izin veriyor (`QrGeneratorController`), ama hata düzeltme seviyesi High iken yaklaşık 1273+ karakterde `endroid/qr-code` "Data too big" fırlatıyor. Karar: `max`'ı ~1200'e indirmek veya hatayı 422'ye çevirmek. Henüz düzeltilmedi.
- [ ] **`.env` yedeği gitignore'da değil** — `.gitignore` yalnız `.env.backup` içeriyor; `.env.backup*` deseni eklenmeli (onay gerekir). Dosya içeriği okunmadı.
- [ ] **Yedek dosyaları dizinde** — `*.backup-*`, `*.before-*`, `*.bak*` dosyaları ağaçta duruyor; `README.md.yedek-20261111` de git'e girmemiş. Silme veya taşıma kararı kullanıcıda.
- [ ] **Boost talimatı** — `CLAUDE.md` sonundaki `<laravel-boost-guidelines>` bloğu korunuyor; `laravel/boost` `composer.json` `require-dev` içinde. Kurulum/kaldırma kararı kullanıcıda.

## 🚧 Devam Eden

- [ ] **NFC kart envanteri ve yazma/doğrulama akışı** — `Business`, `NfcCard`, Filament `Businesses` ve `NfcCards` kaynakları, `POST /nfc/cards/{card}/written|verified` (web ve API). Dev'de testli (244 test geçiyor); canlıya uygulanmış (migration batch 6). Commit ve PR bekliyor.
- [ ] **QR tasarım yönetimi** (Filament `QrDesigns` + `/qr/designs`) — silme ve yeniden adlandırma yok; ayrıntı `backlog.md`.

## ✅ Tamamlanan

- [x] 2026-11-11 — Md dosyaları (`CLAUDE.md`, `README.md`, `architect.md`, `backlog.md`, `session.md`, `task.md`) dev kopyasındaki kodla karşılaştırılarak güncellendi.
- [x] 2026-11-11 — Dev test paketi: 244 test geçiyor (831 doğrulama).
- [x] 2026-11-06 — UTM oluşturucu (`/utm-builder`) ve alan yardımı; WiFi QR (`/wifi-qr`); `NfcCard` mass assignment sertleştirmesi (`status` ve zaman damgaları yalnız kod tarafından yazılır).
- [x] 2026-11-06 — `link_projects.url_hash` ve `(user_id, type, url_hash)` benzersiz indeksi. Eski "tekrar kayıt yarışı" maddesi bu migration ile kapandı (`2026_10_26_152352`).
- [x] 2026-11-06 — Logo yanıtı: `QrLogoController` `setPrivate()` ve `Cache-Control: private, no-store` gönderiyor (önceki `public` sorunu giderildi; kod okunarak doğrulandı, ayrı test yok).
- [x] 2026-10-27 — NFC/entegrasyon değişiklikleri `main`'e commit edildi; CI (`tests.yml`) ve PostgreSQL doğrulaması eklendi.
- [x] 2026-10-26 — Test paketi yazıldı: QR üretimi, tasarım ve logo uçları, Google arama ve Places servisi, `LinkProject` URL doğrulaması, `/nfc-studio`, Sanctum API, Filament sayfaları, herkese açık sayfalar. Mutasyon denemesi: yönlendirme düzeltmesi geri alınınca 6 test kırıldı.
- [x] 2026-10-26 — Port çakışması giderildi: dev web `18086` → `18087`. Canlı `18085` etkilenmedi.
- [x] 2026-10-26 — Oturumsuz `auth` isteğinde 500 giderildi: `bootstrap/app.php` içinde `redirectGuestsTo` ile `/admin/login`'e yönlendirme.
- [x] 2026-10-26 — Google yorum arama ucu `auth` + `throttle:10,1` + `is_admin` ile korunuyor.
- [x] 2026-10-25 — `link_projects` ve `personal_access_tokens` migration'ları, Sanctum ve NFC proje API'si (`GET /api/nfc/projects`).
- [x] 2026-10-22 — `users.is_admin` ve `qr_designs` migration'ları; QR üretici, logo yükleme ve tasarım kaydı rotaları.
