# CLAUDE.md — Generator Studio

Laravel 13 uygulaması (PHP 8.4-FPM + nginx, Docker Compose). Canlı adres: `https://nfc.carimatik.com.tr`. İçinde QR kod üretici (PNG/SVG), kayıtlı QR tasarımları ve logo yükleme (yalnız yönetici), Google yorum araması, Instagram takip bağlantısı ve NFC bağlantı projeleri (`LinkProject`, Sanctum API) bulunur. Filament 5 yönetim paneli (`/admin`): `QrDesigns` sayfası ve `LinkProjects` kaynağı.

## Konum

- **Bu dizin geliştirme kopyasıdır:** `/opt/generator-studio-dev` (compose adı `generator-studio-dev`; `./` → konteyner `/var/www/html`). **Canlı kurulum** `/opt/generator-studio` içindedir; oradaki dosyalara dokunma.
- **Git deposu yok.** Yedek düzeni dosya adı soneki ile: `*.backup-YYYYMMDD-HHMMSS` (örn. `routes/web.php.backup-…`, `app/Models/User.php.before-admin`). Bir dosyayı büyük ölçüde değiştirmeden önce bu düzende yedeğini al.
- Dev konteynerleri: `generator-studio-dev-app` (PHP-FPM, `healthy`), `generator-studio-dev-web` (nginx, `127.0.0.1:18086 → 80`). Canlı: `generator-studio-app` / `generator-studio-web` (`127.0.0.1:18085`).
- Dev, canlıdan **ayrı veritabanı** kullanır; `APP_ENV=local`, `APP_DEBUG=true`. Host nginx sitesi (`/etc/nginx/sites-available/nfc-generator`) yalnız canlı içindir.
- Host'ta PHP kurulu değil; `php`/`artisan`/`vendor/bin/pint` komutları `docker exec generator-studio-dev-app …` ile çalışır.

## Çalıştırma

```bash
cd /opt/generator-studio-dev && docker compose up -d        # app + web
docker exec generator-studio-dev-app php artisan migrate:status
docker exec generator-studio-dev-app php artisan route:list --except-vendor
docker logs --tail 100 generator-studio-dev-app
curl -i http://127.0.0.1:18086/up
```

## Proje md dosyaları

`README.md` (özet), `architect.md` (mimari), `task.md` (görevler ve açık sorunlar), `backlog.md` (fikir/teknik borç), `session.md` (oturum günlüğü). Anlamlı bir iş bitince `session.md` ve `task.md` güncellenir.

## Teknik özet

- PHP `^8.3` (imaj `php:8.4-fpm-bookworm`), Laravel `^13.17`, Filament `^5.0`, `endroid/qr-code ^6.1`.
- Veritabanı: ortak PostgreSQL (`postgres-ortak`, veritabanı `generator_studio`, ağ `ortak-db`).
- Kuyruk `sync`, önbellek `file`, mail `log`, dosya diski `local` (logolar `storage/app/private`).
- Rotalar (`routes/web.php`): `/`, `/qr`, `/google-yorum`, `/instagram-takip` herkese açık; `/qr/generate` 30 istek/dk; `/qr/designs` GET/POST yönetici kontrolü denetleyicide (`abort_unless is_admin`); `/qr/logos`, `/google-yorum/ara`, `/baglantilar/kaydet`, `/nfc-studio` `auth` + `is_admin`.
- API (`routes/api.php`): `GET /api/nfc/projects` — `auth:sanctum`, token yetkisi `nfc:read`, `is_admin`, 30 istek/dk.
- Migration'lar: `users.is_admin`, `qr_designs` (2026-10-22), `link_projects`, `personal_access_tokens` (2026-10-25).
- Bilinen sorun: oturumsuz kullanıcı `auth` korumalı web rotasına girince 500 alır (`login` adlı rota yok); ayrıntı `task.md`.

## Kurallar

- `.env` ve `.env.backup-*` dosyalarının değerleri md/git/zip'e yazılmaz; yalnız değişken adı geçer.
- Yeni rota eklerken `// QR_..._ROUTES` ve `// QR_..._STORAGE` başlık satırlarını silme (bir araç kullanıyor olabilir; `web.php` başında not var).
- Yönetici kontrolü `is_admin` ile denetleyicide yapılır; yeni yönetici uç noktası eklerken aynısını uygula.
- `compose.yaml` içindeki dev portu `127.0.0.1:18086`, `/opt/ipad1vnc/docker-compose.yml` ile çakışıyor (bkz. `task.md`); port değişikliği kullanıcı kararıyla yapılır.
- Laravel Boost kurulum talimatı (aşağıdaki boilerplate) bu projede uygulanmadı; `composer.json` değişikliği yalnız kullanıcı kararıyla yapılır.

---

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.4. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>
