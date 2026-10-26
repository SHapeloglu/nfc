<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QrLogoControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const LOGO = '123e4567-e89b-12d3-a456-426614174000.png';

    public function test_guest_upload_redirects_to_the_admin_login(): void
    {
        $this->post('/qr/logos', ['logo' => UploadedFile::fake()->image('logo.png')])
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_guest_upload_with_json_returns_401(): void
    {
        $this->postJson('/qr/logos', ['logo' => UploadedFile::fake()->image('logo.png')])->assertUnauthorized();
    }

    public function test_non_admin_cannot_upload_a_logo(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/qr/logos', ['logo' => UploadedFile::fake()->image('logo.png')])
            ->assertForbidden();

        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_admin_uploads_a_png_into_their_own_private_folder_with_a_uuid_name(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/qr/logos', [
            'logo' => UploadedFile::fake()->image('benim logom.png'),
        ]);

        $response->assertOk()->assertJsonPath('message', 'Logo yuklendi.');
        $filename = $response->json('filename');
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.png$/', $filename);
        $this->assertStringNotContainsString('logom', $filename);
        Storage::disk('local')->assertExists("qr-logos/{$admin->id}/{$filename}");
        $this->assertSame(route('qr.logos.show', ['filename' => $filename]), $response->json('url'));
    }

    public function test_admin_can_upload_a_jpeg(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/qr/logos', [
            'logo' => UploadedFile::fake()->image('logo.jpg'),
        ]);

        $response->assertOk();
        $this->assertStringEndsWith('.jpg', $response->json('filename'));
    }

    public function test_rejects_a_missing_file(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/qr/logos', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['logo']);
    }

    public function test_rejects_files_that_are_not_png_or_jpeg_images(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/qr/logos', ['logo' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')])
            ->assertUnprocessable()->assertJsonValidationErrors(['logo']);
        $this->actingAs($admin)->postJson('/qr/logos', ['logo' => UploadedFile::fake()->image('anim.gif')])
            ->assertUnprocessable()->assertJsonValidationErrors(['logo']);

        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_rejects_a_logo_larger_than_1_mb(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/qr/logos', ['logo' => UploadedFile::fake()->image('big.png')->size(2048)])
            ->assertUnprocessable()->assertJsonValidationErrors(['logo']);

        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_guest_cannot_view_a_logo(): void
    {
        $this->get('/qr/logos/'.self::LOGO)->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_non_admin_cannot_view_a_logo(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/qr/logos/'.self::LOGO)->assertForbidden();
    }

    public function test_admin_views_their_own_logo_without_caching(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        Storage::disk('local')->put("qr-logos/{$admin->id}/".self::LOGO, 'logo-bytes');

        $response = $this->actingAs($admin)->get('/qr/logos/'.self::LOGO);

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_viewing_another_admins_logo_returns_404(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();
        Storage::disk('local')->put("qr-logos/{$other->id}/".self::LOGO, 'secret');

        $this->actingAs($admin)->get('/qr/logos/'.self::LOGO)->assertNotFound();
    }

    public function test_viewing_a_missing_logo_returns_404(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/qr/logos/'.self::LOGO)->assertNotFound();
    }

    public function test_filenames_that_are_not_uuid_images_do_not_match_the_route(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/qr/logos/..%2F..%2F.env')->assertNotFound();
        $this->actingAs($admin)->get('/qr/logos/logo.png')->assertNotFound();
        $this->actingAs($admin)->get('/qr/logos/'.str_replace('.png', '.php', self::LOGO))->assertNotFound();
    }
    public function test_logo_response_is_private_and_not_cached(): void
    {
        Storage::fake('local');

        $admin = User::factory()->admin()->create();

        Storage::disk('local')->put(
            "qr-logos/{$admin->id}/".self::LOGO,
            'logo-bytes'
        );

        $response = $this->actingAs($admin)
            ->get('/qr/logos/'.self::LOGO);

        $response->assertOk();

        $cacheControl = $response->headers->get('Cache-Control');

        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringNotContainsString('public', $cacheControl);
    }
}
