# task.md — Generator Studio (dev) Görevler

Bu dosya `/opt/generator-studio-dev` içindir. 2026-10-26 incelemesiyle yenilendi; 2026-10-27'de NFC uzlaştırma durumuna göre güncellendi. Önceki sürümler: `/root/yedekler/gs-dev-md-oncesi-20261027/` (10-27 öncesi), `task.md.backup-20261026-113902` (10-26 öncesi).

## 🔜 Sıradaki (kullanıcı kararı bekliyor)
- [ ] (2026-10-27, `root-51` oturum devri) Dev `.env` `APP_URL` hâlâ 18086; dev web 18087'de, değeri düzelt (`.env` değişikliği kullanıcı onayıyla).
- [ ] (2026-10-27, `root-51` oturum devri) Telefondan HTTPS erişim yolu yok: gerçek Android + NTAG testi için seçenekleri çıkar (ör. geçici HTTPS alt alan / tünel) ve kullanıcıya sun.
- [ ] (2026-10-27, `root-51` oturum devri) Girişli ekranların (`/nfc-studio`, Filament işletme ve kart kaynakları) elle gözden geçirilmesi yalnız kısmen yazılı; tarayıcıda uçtan uca bak, sonucu buraya yaz.
- [ ] (2026-10-27) `topluisler.md` #255 içinde `d8e7590` CI'ı için hem «bakılmadı» hem «yeşil» yazıyor: GitHub Actions'tan gerçek durumu doğrula, tek duruma indir.
- [ ] (2026-10-27, `root-83` oturum devri) `QrGeneratorController.phpnano.save` nano artığı #254/#255 ile ilgili; silme/ignore adımı `/root/yedekler/nfc-commit-hazirlik-20261027.sh` betiğinde hazır.

- [ ] **Commit kararı** — Dev çalışma ağacında 49 commit edilmemiş değişiklik var (21 değişmiş + ~28 yeni dosya, +710/−316). İçerik `origin/feature/integration` (d8e7590) ile aynı; `main` (HEAD `5c1ee86`) üzerinde commit yok. Hazır üç commit önerisi: `/root/yedekler/nfc-commit-hazirlik-20261027.sh` (1: editör artıkları, 2: kod+test+yapılandırma, 3: belgeler). Script ÇALIŞTIRILMADI. Çalıştırmadan önce: (a) script commit mesajlarındaki `Co-Authored-By` satırı Sonnet 5.5 yazıyor; Haiku 5.5 olmalı; (b) `php artisan test` ile test sayısı doğrulanmalı (script 151 bekliyor; 2026-10-27 uzlaştırma notunda 228 yazıyor). Kod commit'i kullanıcı onayıyla yapılır; push ayrıca onay ister.
- [ ] **`tag_uid` benzersizlik kararı** — `nfc_cards` migration'ında `unique(user_id, tag_uid)` görünüyor (dosya 2026-10-27 00:23'te değişti; başka bir oturum yazmış olabilir). Karar kullanıcıya ait: bu kısıt kalacak mı? Ayrıca "Yeniden hazırla" eylemi `tag_uid` alanını da temizliyor; UID'nin korunması isteniyor mu? Rapor (`NFC-254-255.md` §2 #6) bu kısıtı "yok" diyor; rapor bu noktada güncel değil.
- [ ] **PR #1 Draft çıkışı** — `feature/integration` (Draft PR #1). `e5b3452` CI yeşil; `d8e7590` CI sonucuna bakılmadı. Draft'tan çıkış, gerçek Android + NTAG testinden sonra kullanıcı kararı.
- [ ] **Gerçek Android + NTAG testi** — Dev `127.0.0.1:18087`; dışarıdan HTTPS gerekir. Yaz/doğrula akışı, çift `reading` olayı (#255-5), NTAG UID davranışı. Canlıya geçişin önkoşulu; bu test yapılmadan canlıya geçilmez.
- [ ] **Canlıya geçiş bakım penceresi** — Kesinti kabulü (nfc.carimatik.com.tr), yedek, canlı veritabanında `link_projects` (user_id, type, target_url) tekrar kontrolü (salt okunur), 3 bekleyen migration (url_hash, businesses, nfc_cards). Adımlar: `/root/yedekler/is-analiz-20261026/rapor/NFC-254-255.md` §5.
- [ ] **Filament kayıtlarında `metadata` boş** — `CreateLinkProject` ve `LinkProjectForm` `metadata` üretmiyor; API denetleyicisi Google için `place_id`, Instagram için `username` yazıyor. Ayrıştırma mantığı tek yerde (örn. bir servis/kural sınıfı) toplanıp ikisi de kullanmalı. Form doğrulaması ile `LinkProjectController` kuralları şu an iki yerde kopya.
- [ ] **Boost talimatı** — `CLAUDE.md` sonundaki `<laravel-boost-guidelines>` bloğu korunuyor; `laravel/boost` `composer.json` `require-dev` içinde var. Kurulum/kaldırma kararı kullanıcıda.

## 🚧 Devam Eden

- [ ] NFC/bağlantı projeleri ve NFC envanteri (`LinkProject`, `Business`, `NfcCard`, `LinkTargetService`, `/nfc-studio`, `/api/nfc/projects`, `/wifi-qr`, `/utm-builder`) — kod dev çalışma ağacında; commit ve gerçek cihaz testi bekliyor. Canlı henüz eski sürümde.
- [ ] QR tasarım yönetimi (Filament `QrDesigns` + `/qr/designs`) — tamamlanma durumu netleştirilmeli.

## ✅ Tamamlanan

- [x] 2026-10-27 — Dev ↔ canlı karşılaştırması (salt okunur, `diff -rq`, yaklaşık 105 satır). Canlı `/opt/generator-studio` git deposu DEĞİL; dev `/opt/generator-studio-dev` git deposu (`main`). Rapor: `/root/yedekler/is-analiz-20261026/rapor/NFC-254-255.md`. (Önceki "canlı/dev farkı raporu alınamadı" maddesi kapandı.)
- [x] 2026-10-27 — 49 commit edilmemiş değişiklik sınıflandırıldı (kod, test, yapılandırma, belge, çöp) ve 3 commit grubuna ayrıldı; rapor: `/root/yedekler/is-analiz-20261026/rapor/NFC-commitsiz.md`. Hazırlık scripti yazıldı, çalıştırılmadı.
- [x] 2026-10-27 — Dev çalışma ağacı `origin/feature/integration` (d8e7590) içeriğiyle eşitlendi (`app routes resources database tests`, `bootstrap/app.php`, `link-save.js`, `config/services.php`); dev veritabanında `businesses` ve `nfc_cards` migration'ları uygulandı; dev verisi korundu. Yedek: `/root/yedekler/gs-dev-oncesi-uzlastirma-20261026-2353` (dizin + dev DB).
- [x] 2026-10-26 — `link_projects` tekrar kaydı yarışı kapandı: `url_hash` (sha256) sütunu + `unique(user_id,type,url_hash)` migration'ı (dev DB'ye uygulandı, tekrar kayıt yoktu); hash model `saving` olayıyla her kayıt yolunda atanır; API denetleyicisi indeks ihlalini yakalayıp 200 + `already_exists` döner; Filament formuna `unique` kuralı eklendi (500 yerine form hatası). Testler: `LinkProjectTest`, yarış testi, Filament tekrar senaryoları.
- [x] 2026-10-26 — Route closure'ları denetleyiciye taşındı (`NfcStudioController`, `NfcProjectController`, `CurrentUserController`, `/` → `Route::view`); `route:cache` çalışıyor (doğrulandı, ardından `route:clear`). Davranış testleri değişmeden geçti.
- [x] 2026-10-26 — Filament testleri: `LinkProjectResourceTest` (18), `QrDesignsPageTest` (4). Tam paket o gün 151 test, 486 doğrulama.
- [x] 2026-10-26 — Dev web konteynerine uygulama düzeyinde sağlık kontrolü (`wget /up`); `healthy`.
- [x] 2026-10-26 — Yedek dosyaları (68) dizin dışına taşındı: `/opt/generator-studio-dev-yedek/` (yollar korunmuş, `MANIFEST.txt`). `.env*` yedeklerine dokunulmadı; dev dizininde yoktu. `.gitignore` `.env.*` ve yedek desenlerini kapsıyor.
- [x] 2026-10-26 — `/qr/generate` uzun içerikte 500 ve logo yanıtında `Cache-Control: public` kusurları `5c1ee86` commit'iyle giderilmiş (kullanıcı); testlerle doğrulandı (422, `no-store, private`).
- [x] 2026-10-26 — Test paketi yazıldı: 121 test (`13ac28e`). Kapsam: QR üretimi (sınırlar, 429), tasarım ve logo uçları (403/404/422, başka kullanıcının kaydı), Google arama ve Places servisi (önbellek, hata, `Http::fake`), `LinkProject` URL doğrulaması, `/nfc-studio`, Sanctum API (`nfc:read`), `canAccessPanel`, Filament sayfaları, herkese açık sayfalar. Mutasyon denemesi: yönlendirme düzeltmesi geri alınınca 6 test kırıldı. Fabrikalar: `UserFactory::admin()`, `QrDesignFactory`, `LinkProjectFactory`.
- [x] 2026-10-26 — Port çakışması giderildi: dev web `18086` → `18087` (`compose.yaml`, konteyner yeniden oluşturuldu). `18086` `/opt/ipad1vnc` için serbest; canlı `18085` etkilenmedi.
- [x] 2026-10-26 — Oturumsuz `auth` isteğinde 500 giderildi (dev): `bootstrap/app.php` içinde `redirectGuestsTo` ile `/admin/login`'e yönlendirme. Doğrulama: `/nfc-studio` ve `/qr/logos/…` 302 → `/admin/login`; JSON isteği 401; `/api/*` 401. Canlıda bu düzeltme YOK (bkz. canlıya geçiş).
- [x] 2026-10-26 — Proje incelemesi; md dosyaları dev kopyasına göre yenilendi.
- [x] 2026-10-26 — Doğrulama: Google yorum arama ucu `auth` + `throttle:10,1` + `is_admin` ile korunuyor.
- [x] 2026-10-25 — `link_projects` ve `personal_access_tokens` migration'ları, Sanctum ve NFC API.
- [x] 2026-10-22 — `users.is_admin` ve `qr_designs` migration'ları; QR üretici, logo yükleme ve tasarım kaydı rotaları.
