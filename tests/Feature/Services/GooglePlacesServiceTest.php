<?php

namespace Tests\Feature\Services;

use App\Services\GooglePlacesService;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class GooglePlacesServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.google_places.key' => 'test-key']);
    }

    public function test_drops_places_without_id_or_name_and_keeps_only_10(): void
    {
        $places = [
            ['id' => '', 'displayName' => ['text' => 'Kimliksiz']],
            ['id' => 'p-no-name', 'displayName' => []],
        ];
        for ($i = 1; $i <= 12; $i++) {
            $places[] = ['id' => "p{$i}", 'displayName' => ['text' => "Yer {$i}"]];
        }
        Http::preventStrayRequests();
        Http::fake(['places.googleapis.com/*' => Http::response(['places' => $places])]);

        $results = app(GooglePlacesService::class)->search('kahve');

        $this->assertCount(10, $results);
        $this->assertSame(['place_id' => 'p1', 'name' => 'Yer 1', 'address' => ''], $results[0]);
        $this->assertSame('p10', $results[9]['place_id']);
    }

    public function test_returns_an_empty_list_when_google_finds_nothing(): void
    {
        Http::preventStrayRequests();
        Http::fake(['places.googleapis.com/*' => Http::response([])]);

        $this->assertSame([], app(GooglePlacesService::class)->search('olmayan yer'));
    }

    public function test_repeated_search_is_served_from_cache_ignoring_case_and_outer_spaces(): void
    {
        Http::preventStrayRequests();
        Http::fake(['places.googleapis.com/*' => Http::response(['places' => [
            ['id' => 'p1', 'displayName' => ['text' => 'Yer']],
        ]])]);
        $service = app(GooglePlacesService::class);

        $first = $service->search('Kahve Evi');
        $second = $service->search('  kahve evi ');

        $this->assertSame($first, $second);
        Http::assertSentCount(1);
    }

    public function test_failed_responses_are_not_cached(): void
    {
        Http::preventStrayRequests();
        Http::fake(['places.googleapis.com/*' => Http::sequence()
            ->push([], 500)
            ->push(['places' => [['id' => 'p1', 'displayName' => ['text' => 'Yer']]]])]);
        $service = app(GooglePlacesService::class);

        try {
            $service->search('kahve evi');
            $this->fail('İlk çağrı RuntimeException fırlatmalıydı.');
        } catch (RuntimeException $e) {
            $this->assertSame('Google Places HTTP 500', $e->getMessage());
        }

        $this->assertSame('p1', $service->search('kahve evi')[0]['place_id']);
        Http::assertSentCount(2);
    }

    public function test_rejects_too_short_or_too_long_queries_without_calling_google(): void
    {
        Http::preventStrayRequests();
        $service = app(GooglePlacesService::class);

        foreach (['  ab  ', str_repeat('a', 121)] as $query) {
            try {
                $service->search($query);
                $this->fail('RuntimeException bekleniyordu.');
            } catch (RuntimeException $e) {
                $this->assertSame('Gecersiz arama metni.', $e->getMessage());
            }
        }

        Http::assertNothingSent();
    }

    public function test_throws_when_the_api_key_is_missing(): void
    {
        config(['services.google_places.key' => null]);
        Http::preventStrayRequests();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Google API anahtari bulunamadi.');

        app(GooglePlacesService::class)->search('kahve evi');
    }
}
