<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleReviewControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_renders_the_review_page_for_guests(): void
    {
        $this->get('/google-yorum')->assertOk();
    }

    public function test_guest_search_redirects_to_the_admin_login(): void
    {
        Http::fake();

        $this->post('/google-yorum/ara', ['query' => 'kahve dükkanı'])
            ->assertRedirect(route('filament.admin.auth.login'));

        Http::assertNothingSent();
    }

    public function test_non_admin_search_is_forbidden_and_never_calls_google(): void
    {
        Http::fake();

        $this->actingAs(User::factory()->create())
            ->postJson('/google-yorum/ara', ['query' => 'kahve dükkanı'])
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_rejects_queries_shorter_than_3_or_longer_than_120_characters(): void
    {
        Http::fake();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/google-yorum/ara', ['query' => 'ab'])
            ->assertUnprocessable()->assertJsonValidationErrors(['query']);
        $this->actingAs($admin)->postJson('/google-yorum/ara', ['query' => str_repeat('a', 121)])
            ->assertUnprocessable()->assertJsonValidationErrors(['query']);
        $this->actingAs($admin)->postJson('/google-yorum/ara', [])
            ->assertUnprocessable()->assertJsonValidationErrors(['query']);

        Http::assertNothingSent();
    }

    public function test_admin_search_returns_the_mapped_places(): void
    {
        config(['services.google_places.key' => 'test-key']);
        Http::preventStrayRequests();
        Http::fake([
            'places.googleapis.com/*' => Http::response(['places' => [
                ['id' => 'ChIJabc', 'displayName' => ['text' => 'Kahve Evi'], 'formattedAddress' => 'Bağdat Cd. 1, İstanbul'],
            ]]),
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->postJson('/google-yorum/ara', ['query' => 'Kahve Evi İstanbul'])
            ->assertOk()
            ->assertExactJson(['results' => [
                ['place_id' => 'ChIJabc', 'name' => 'Kahve Evi', 'address' => 'Bağdat Cd. 1, İstanbul'],
            ]]);

        Http::assertSent(fn ($request) => $request->hasHeader('X-Goog-Api-Key', 'test-key')
            && $request['textQuery'] === 'Kahve Evi İstanbul'
            && $request['languageCode'] === 'tr');
    }

    public function test_google_failure_returns_502_with_a_generic_message(): void
    {
        config(['services.google_places.key' => 'test-key']);
        Http::preventStrayRequests();
        Http::fake(['places.googleapis.com/*' => Http::response(['error' => 'quota exceeded: secret detail'], 429)]);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->postJson('/google-yorum/ara', ['query' => 'Kahve Evi']);

        $response->assertStatus(502)->assertExactJson(['message' => 'Google isletme aramasi basarisiz.']);
        $this->assertStringNotContainsString('secret detail', $response->getContent());
    }

    public function test_missing_api_key_returns_502_without_calling_google(): void
    {
        config(['services.google_places.key' => null]);
        Http::preventStrayRequests();

        $this->actingAs(User::factory()->admin()->create())
            ->postJson('/google-yorum/ara', ['query' => 'Kahve Evi'])
            ->assertStatus(502);
    }
}
