<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\LinkProject;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LinkProjectControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const GOOGLE_URL = 'https://search.google.com/local/writereview?placeid=ChIJN1t_tDeuEmsRUsoyG83frY4';

    private const INSTAGRAM_URL = 'https://www.instagram.com/ornek.hesap?xtok=abcdEFGH1234';

    public function test_guest_is_redirected_to_the_admin_login(): void
    {
        $this->post('/baglantilar/kaydet', ['name' => 'x', 'type' => 'google_review', 'target_url' => self::GOOGLE_URL])
            ->assertRedirect(route('filament.admin.auth.login'));

        $this->assertDatabaseCount('link_projects', 0);
    }

    public function test_non_admin_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/baglantilar/kaydet', ['name' => 'x', 'type' => 'google_review', 'target_url' => self::GOOGLE_URL])
            ->assertForbidden();

        $this->assertDatabaseCount('link_projects', 0);
    }

    public function test_saves_a_google_review_link_with_the_place_id_as_metadata(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/baglantilar/kaydet', [
            'name' => 'Kahve Evi',
            'type' => 'google_review',
            'target_url' => self::GOOGLE_URL,
        ]);

        $response->assertCreated()->assertJsonPath('already_exists', false);
        $project = LinkProject::findOrFail($response->json('id'));
        $this->assertSame($admin->id, $project->user_id);
        $this->assertSame('Kahve Evi', $project->name);
        $this->assertSame(self::GOOGLE_URL, $project->target_url);
        $this->assertSame(['place_id' => 'ChIJN1t_tDeuEmsRUsoyG83frY4'], $project->metadata);
    }

    public function test_saves_an_instagram_link_with_the_username_but_not_the_token(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/baglantilar/kaydet', [
            'name' => 'Instagram',
            'type' => 'instagram_follow',
            'target_url' => self::INSTAGRAM_URL,
        ]);

        $response->assertCreated();
        $this->assertSame(['username' => 'ornek.hesap'], LinkProject::findOrFail($response->json('id'))->metadata);
    }

    public function test_saving_the_same_link_again_returns_the_existing_record(): void
    {
        $admin = User::factory()->admin()->create();
        $existing = LinkProject::factory()->for($admin)->create(['target_url' => self::GOOGLE_URL, 'type' => 'google_review']);

        $this->actingAs($admin)->postJson('/baglantilar/kaydet', [
            'name' => 'Başka ad',
            'type' => 'google_review',
            'target_url' => self::GOOGLE_URL,
        ])->assertOk()->assertJson(['id' => $existing->id, 'already_exists' => true]);

        $this->assertDatabaseCount('link_projects', 1);
    }

    public function test_another_admins_identical_link_does_not_count_as_a_duplicate(): void
    {
        $admin = User::factory()->admin()->create();
        $foreign = LinkProject::factory()->for(User::factory()->admin())->create(['target_url' => self::GOOGLE_URL]);

        $response = $this->actingAs($admin)->postJson('/baglantilar/kaydet', [
            'name' => 'Benim',
            'type' => 'google_review',
            'target_url' => self::GOOGLE_URL,
        ]);

        $response->assertCreated();
        $this->assertNotSame($foreign->id, $response->json('id'));
        $this->assertDatabaseCount('link_projects', 2);
    }

    public function test_ignores_a_user_id_sent_in_the_payload(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/baglantilar/kaydet', [
            'name' => 'Sızdırma',
            'type' => 'google_review',
            'target_url' => self::GOOGLE_URL,
            'user_id' => $other->id,
        ]);

        $this->assertSame($admin->id, LinkProject::findOrFail($response->json('id'))->user_id);
    }

    public function test_empty_payload_returns_validation_errors(): void
    {
        $this->actingAs(User::factory()->admin()->create())->postJson('/baglantilar/kaydet', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'type', 'target_url']);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function invalidLinks(): array
    {
        $google = 'https://search.google.com/local/writereview?placeid=ChIJN1t_tDeuEmsRUsoyG83frY4';
        $instagram = 'https://www.instagram.com/ornek.hesap?xtok=abcdEFGH1234';

        return [
            'google: http şeması' => ['google_review', str_replace('https', 'http', $google), 'target_url'],
            'google: yanlış alan adı' => ['google_review', 'https://evil.example/local/writereview?placeid=ChIJN1t_tDeuEmsRUsoyG83frY4', 'target_url'],
            'google: yanlış yol' => ['google_review', 'https://search.google.com/search?placeid=ChIJN1t_tDeuEmsRUsoyG83frY4', 'target_url'],
            'google: placeid yok' => ['google_review', 'https://search.google.com/local/writereview', 'target_url'],
            'google: placeid çok kısa' => ['google_review', 'https://search.google.com/local/writereview?placeid=abc', 'target_url'],
            'google: placeid geçersiz karakter' => ['google_review', 'https://search.google.com/local/writereview?placeid=ChIJN1t<script>alert1', 'target_url'],
            'google: port var' => ['google_review', 'https://search.google.com:8443/local/writereview?placeid=ChIJN1t_tDeuEmsRUsoyG83frY4', 'target_url'],
            'google: kullanıcı bilgisi var' => ['google_review', 'https://user:pw@search.google.com/local/writereview?placeid=ChIJN1t_tDeuEmsRUsoyG83frY4', 'target_url'],
            'google: fragment var' => ['google_review', $google.'#bolum', 'target_url'],
            'instagram: yanlış alan adı' => ['instagram_follow', 'https://instagram.evil.example/ornek.hesap?xtok=abcdEFGH1234', 'target_url'],
            'instagram: xtok yok' => ['instagram_follow', 'https://www.instagram.com/ornek.hesap', 'target_url'],
            'instagram: xtok çok kısa' => ['instagram_follow', 'https://www.instagram.com/ornek.hesap?xtok=abc', 'target_url'],
            'instagram: kullanıcı adı geçersiz' => ['instagram_follow', 'https://www.instagram.com/ornek/hesap?xtok=abcdEFGH1234', 'target_url'],
            'instagram: kullanıcı adı 30 karakterden uzun' => ['instagram_follow', 'https://www.instagram.com/'.str_repeat('a', 31).'?xtok=abcdEFGH1234', 'target_url'],
            'instagram: http şeması' => ['instagram_follow', str_replace('https', 'http', $instagram), 'target_url'],
            'tür bilinmiyor' => ['facebook', $google, 'type'],
            'adres url değil' => ['google_review', 'bu bir url degil', 'target_url'],
        ];
    }

    #[DataProvider('invalidLinks')]
    public function test_rejects_invalid_links_with_422_and_saves_nothing(string $type, string $url, string $errorField): void
    {
        $this->actingAs(User::factory()->admin()->create())->postJson('/baglantilar/kaydet', [
            'name' => 'Geçersiz',
            'type' => $type,
            'target_url' => $url,
        ])->assertUnprocessable()->assertJsonValidationErrors([$errorField]);

        $this->assertDatabaseCount('link_projects', 0);
    }

    public function test_rejects_a_name_longer_than_150_characters(): void
    {
        $this->actingAs(User::factory()->admin()->create())->postJson('/baglantilar/kaydet', [
            'name' => str_repeat('a', 151),
            'type' => 'google_review',
            'target_url' => self::GOOGLE_URL,
        ])->assertUnprocessable()->assertJsonValidationErrors(['name']);
    }
}
