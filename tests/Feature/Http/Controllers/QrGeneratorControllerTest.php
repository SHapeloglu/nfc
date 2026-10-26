<?php

namespace Tests\Feature\Http\Controllers;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class QrGeneratorControllerTest extends TestCase
{
    /**
     * @return array<string, string>
     */
    private function validPayload(): array
    {
        return [
            'content' => 'https://example.com',
            'size' => '300',
            'foreground' => '#000000',
            'background' => '#ffffff',
            'format' => 'png',
        ];
    }

    public function test_renders_the_generator_page_for_guests(): void
    {
        $this->get('/qr')->assertOk();
    }

    public function test_png_request_returns_a_png_image_without_caching(): void
    {
        $response = $this->post('/qr/generate', $this->validPayload());

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertSame("\x89PNG", substr($response->getContent(), 0, 4));
    }

    public function test_svg_request_returns_an_svg_document_with_the_chosen_colors(): void
    {
        $response = $this->post('/qr/generate', [
            ...$this->validPayload(),
            'format' => 'svg',
            'foreground' => '#ff0000',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('image/svg+xml', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('<svg', $response->getContent());
        $this->assertStringContainsString('#ff0000', $response->getContent());
    }

    public function test_empty_payload_returns_validation_errors_for_every_field(): void
    {
        $this->postJson('/qr/generate', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['content', 'size', 'foreground', 'background', 'format']);
    }

    /**
     * @return array<string, array{0: string, 1: mixed}>
     */
    public static function invalidInputs(): array
    {
        return [
            'içerik 2000 karakterden uzun' => ['content', str_repeat('a', 2001)],
            'boyut 200 altında' => ['size', '199'],
            'boyut 1000 üstünde' => ['size', '1001'],
            'ön renk kısa biçimde' => ['foreground', '#fff'],
            'arka renk renk adı' => ['background', 'red'],
            'bilinmeyen biçim' => ['format', 'gif'],
        ];
    }

    #[DataProvider('invalidInputs')]
    public function test_rejects_invalid_input_with_422(string $field, mixed $value): void
    {
        $this->postJson('/qr/generate', [...$this->validPayload(), $field => $value])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$field]);
    }

    public function test_accepts_the_smallest_size_with_long_content(): void
    {
        $this->post('/qr/generate', [...$this->validPayload(), 'size' => '200', 'content' => str_repeat('a', 1200)])
            ->assertOk();
    }

    public function test_accepts_the_largest_size(): void
    {
        $this->post('/qr/generate', [...$this->validPayload(), 'size' => '1000', 'format' => 'svg'])
            ->assertOk();
    }

    public function test_returns_429_after_30_requests_in_a_minute(): void
    {
        $payload = [...$this->validPayload(), 'format' => 'svg'];

        for ($i = 0; $i < 30; $i++) {
            $this->post('/qr/generate', $payload)->assertOk();
        }

        $this->post('/qr/generate', $payload)->assertTooManyRequests();
    }
}
