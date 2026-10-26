<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function publicPages(): array
    {
        return [
            'ana sayfa' => ['/'],
            'QR üretici' => ['/qr'],
            'Google yorum' => ['/google-yorum'],
            'Instagram takip' => ['/instagram-takip'],
            'sağlık kontrolü' => ['/up'],
        ];
    }

    #[DataProvider('publicPages')]
    public function test_renders_for_guests(string $path): void
    {
        $this->get($path)->assertOk();
    }

    public function test_unknown_pages_return_404(): void
    {
        $this->get('/yok-boyle-bir-sayfa')->assertNotFound();
    }
}
