<?php

namespace Tests\Feature\Services;

use App\Services\LinkTargetService;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LinkTargetServiceTest extends TestCase
{
    public function test_extracts_google_place_id(): void
    {
        $metadata = app(LinkTargetService::class)->metadataFor(
            'google_review',
            'https://search.google.com/local/writereview?placeid=ChIJN1t_tDeuEmsRUsoyG83frY4'
        );

        $this->assertSame(['place_id' => 'ChIJN1t_tDeuEmsRUsoyG83frY4'], $metadata);
    }

    public function test_does_not_store_the_instagram_sharing_token_in_metadata(): void
    {
        $metadata = app(LinkTargetService::class)->metadataFor(
            'instagram_follow',
            'https://www.instagram.com/ornek.hesap?xtok=abcdEFGH1234'
        );

        $this->assertSame(['username' => 'ornek.hesap'], $metadata);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function invalidTargets(): array
    {
        return [
            'google fragment' => ['google_review', 'https://search.google.com/local/writereview?placeid=ChIJN1t_tDeuEmsRUsoyG83frY4#extra'],
            'instagram fragment' => ['instagram_follow', 'https://www.instagram.com/ornek.hesap?xtok=abcdEFGH1234#extra'],
            'instagram port' => ['instagram_follow', 'https://www.instagram.com:8443/ornek.hesap?xtok=abcdEFGH1234'],
            'google user info' => ['google_review', 'https://user:pass@search.google.com/local/writereview?placeid=ChIJN1t_tDeuEmsRUsoyG83frY4'],
            'unknown type' => ['unknown', 'https://search.google.com/local/writereview?placeid=ChIJN1t_tDeuEmsRUsoyG83frY4'],
        ];
    }

    #[DataProvider('invalidTargets')]
    public function test_rejects_unapproved_targets(string $type, string $url): void
    {
        try {
            app(LinkTargetService::class)->metadataFor($type, $url);
            $this->fail('An invalid target was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('target_url', $exception->errors());
        }
    }
}
