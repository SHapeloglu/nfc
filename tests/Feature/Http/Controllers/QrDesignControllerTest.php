<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\QrDesign;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class QrDesignControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validSettings(): array
    {
        return [
            'content' => 'https://example.com',
            'dots' => 'rounded',
            'corners' => 'dot',
            'fg' => '#112233',
            'bg' => '#ffffff',
            'cornerColor' => '#000000',
            'gradient' => 'linear',
            'gradientEnd' => '#445566',
            'size' => '800',
            'margin' => '40',
        ];
    }

    public function test_guest_cannot_list_designs(): void
    {
        $this->getJson('/qr/designs')->assertForbidden();
    }

    public function test_guest_cannot_save_a_design(): void
    {
        $this->postJson('/qr/designs', ['name' => 'x', 'settings' => $this->validSettings()])->assertForbidden();

        $this->assertDatabaseCount('qr_designs', 0);
    }

    public function test_non_admin_cannot_list_or_save_designs(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/qr/designs')->assertForbidden();
        $this->actingAs($user)->postJson('/qr/designs', ['name' => 'x', 'settings' => $this->validSettings()])->assertForbidden();

        $this->assertDatabaseCount('qr_designs', 0);
    }

    public function test_admin_lists_only_own_designs_newest_first(): void
    {
        $admin = User::factory()->admin()->create();
        $older = QrDesign::factory()->for($admin)->create(['name' => 'Eski', 'updated_at' => now()->subDay()]);
        $newer = QrDesign::factory()->for($admin)->create(['name' => 'Yeni', 'updated_at' => now()]);
        QrDesign::factory()->for(User::factory()->admin())->create(['name' => 'Başkasının']);

        $response = $this->actingAs($admin)->getJson('/qr/designs');

        $response->assertOk();
        $this->assertSame([$newer->id, $older->id], array_column($response->json(), 'id'));
        $response->assertJsonMissing(['name' => 'Başkasının']);
        $response->assertJsonStructure([['id', 'name', 'settings', 'updated_at']]);
    }

    public function test_admin_saves_a_new_design_owned_by_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/qr/designs', [
            'name' => 'Kampanya',
            'settings' => $this->validSettings(),
        ]);

        $response->assertOk()->assertJsonPath('name', 'Kampanya')->assertJsonPath('message', 'Tasarim kaydedildi.');
        $design = QrDesign::findOrFail($response->json('id'));
        $this->assertSame($admin->id, $design->user_id);

        $expectedSettings = $this->validSettings();
        $savedSettings = $design->settings;
        ksort($expectedSettings);
        ksort($savedSettings);

        $this->assertSame($expectedSettings, $savedSettings);
    }

    public function test_ignores_unknown_settings_keys_and_a_foreign_user_id(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/qr/designs', [
            'name' => 'Sızdırma',
            'user_id' => $other->id,
            'settings' => [...$this->validSettings(), 'evil' => '<script>alert(1)</script>'],
        ]);

        $response->assertOk();
        $design = QrDesign::findOrFail($response->json('id'));
        $this->assertSame($admin->id, $design->user_id);
        $this->assertArrayNotHasKey('evil', $design->settings);
    }

    public function test_admin_updates_an_own_design_without_creating_a_new_one(): void
    {
        $admin = User::factory()->admin()->create();
        $design = QrDesign::factory()->for($admin)->create(['name' => 'Eski ad']);

        $this->actingAs($admin)->postJson('/qr/designs', [
            'id' => $design->id,
            'name' => 'Yeni ad',
            'settings' => $this->validSettings(),
        ])->assertOk()->assertJsonPath('id', $design->id);

        $this->assertDatabaseCount('qr_designs', 1);
        $this->assertSame('Yeni ad', $design->fresh()->name);
        $this->assertSame('rounded', $design->fresh()->settings['dots']);
    }

    public function test_updating_another_admins_design_returns_404_and_keeps_it_unchanged(): void
    {
        $admin = User::factory()->admin()->create();
        $foreign = QrDesign::factory()->for(User::factory()->admin())->create(['name' => 'Başkasının']);

        $this->actingAs($admin)->postJson('/qr/designs', [
            'id' => $foreign->id,
            'name' => 'Ele geçirildi',
            'settings' => $this->validSettings(),
        ])->assertNotFound();

        $this->assertSame('Başkasının', $foreign->fresh()->name);
    }

    public function test_empty_payload_returns_validation_errors(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/qr/designs', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'settings']);
    }

    /**
     * @return array<string, array{0: string, 1: mixed}>
     */
    public static function invalidSettings(): array
    {
        return [
            'bilinmeyen nokta tipi' => ['dots', 'triangle'],
            'bilinmeyen köşe tipi' => ['corners', 'star'],
            'geçersiz ön renk' => ['fg', 'blue'],
            'geçersiz degrade' => ['gradient', 'conic'],
            'izin verilmeyen boyut' => ['size', '123'],
            'izin verilmeyen kenar boşluğu' => ['margin', '15'],
            'logo adı UUID değil' => ['logoFile', '../../etc/passwd.png'],
            'logo boyutu 30 üstünde' => ['logoSize', 31],
            'logo kenar boşluğu izinsiz' => ['logoMargin', 4],
            'içerik 2000 karakterden uzun' => ['content', str_repeat('a', 2001)],
        ];
    }

    #[DataProvider('invalidSettings')]
    public function test_rejects_invalid_settings_with_422_and_saves_nothing(string $key, mixed $value): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/qr/designs', [
            'name' => 'Geçersiz',
            'settings' => [...$this->validSettings(), $key => $value],
        ])->assertUnprocessable()->assertJsonValidationErrors(["settings.$key"]);

        $this->assertDatabaseCount('qr_designs', 0);
    }

    public function test_rejects_a_name_longer_than_150_characters(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/qr/designs', [
            'name' => str_repeat('a', 151),
            'settings' => $this->validSettings(),
        ])->assertUnprocessable()->assertJsonValidationErrors(['name']);
    }

    public function test_saves_a_design_that_references_an_own_uploaded_logo(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $logo = '123e4567-e89b-12d3-a456-426614174000.png';
        Storage::disk('local')->put("qr-logos/{$admin->id}/{$logo}", 'png');

        $response = $this->actingAs($admin)->postJson('/qr/designs', [
            'name' => 'Logolu',
            'settings' => [...$this->validSettings(), 'logoFile' => $logo, 'logoSize' => 20, 'logoMargin' => 5],
        ]);

        $response->assertOk();
        $this->assertSame($logo, QrDesign::findOrFail($response->json('id'))->settings['logoFile']);
    }

    public function test_rejects_a_missing_logo_file_with_422(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/qr/designs', [
            'name' => 'Logosuz',
            'settings' => [...$this->validSettings(), 'logoFile' => '123e4567-e89b-12d3-a456-426614174000.png'],
        ])->assertUnprocessable();

        $this->assertDatabaseCount('qr_designs', 0);
    }

    public function test_rejects_a_logo_uploaded_by_another_admin(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();
        $logo = '123e4567-e89b-12d3-a456-426614174000.png';
        Storage::disk('local')->put("qr-logos/{$other->id}/{$logo}", 'png');

        $this->actingAs($admin)->postJson('/qr/designs', [
            'name' => 'Çalıntı logo',
            'settings' => [...$this->validSettings(), 'logoFile' => $logo],
        ])->assertUnprocessable();

        $this->assertDatabaseCount('qr_designs', 0);
    }
}
