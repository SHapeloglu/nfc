<?php

namespace Tests\Feature\Http\Controllers;

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WifiQrControllerTest extends TestCase
{
    public function test_public_page_renders_without_wifi_secrets(): void
    {
        $this->get('/wifi-qr')
            ->assertOk()
            ->assertSee('WiFi QR Oluşturucu')
            ->assertSee('name="ssid"', false);
    }

    public function test_wifi_page_invalidates_stale_qr_after_credentials_change(): void
    {
        $this->get('/wifi-qr')
            ->assertOk()
            ->assertSee('form.addEventListener(\'input\', invalidateGeneratedQr)', false)
            ->assertSee('form.addEventListener(\'change\', invalidateGeneratedQr)', false)
            ->assertSee('download.removeAttribute(\'href\')', false)
            ->assertSee('if (submittedVersion !== formVersion) return;', false);
    }

    public function test_password_protected_wifi_generates_private_svg(): void
    {
        $response = $this->post('/wifi-qr/generate', [
            'ssid' => 'Guest Network',
            'security' => 'WPA',
            'password' => 'long-secret-123',
            'hidden' => true,
        ])->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $cacheControl = array_map('trim', explode(',', (string) $response->headers->get('Cache-Control')));
        sort($cacheControl);

        $this->assertSame(['no-store', 'private'], $cacheControl);
    }

    public function test_ssid_and_password_spaces_are_preserved_in_qr_payload(): void
    {
        // Laravel'in TrimStrings ara katmanı SSID'yi kırpmamalı; WPA parolasının sonundaki boşluk da anlamlıdır.
        $response = $this->post('/wifi-qr/generate', [
            'ssid' => 'Ag ',
            'security' => 'WPA',
            'password' => 'parola12 ',
        ])->assertOk();

        $expected = (new SvgWriter())->write(new QrCode(
            data: 'WIFI:T:WPA;S:Ag ;P:parola12 ;H:false;;',
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 500,
            margin: 20,
        ))->getString();

        $this->assertSame($expected, $response->getContent());
    }

    public function test_open_wifi_generates_svg_without_password(): void
    {
        $this->post('/wifi-qr/generate', [
            'ssid' => 'Open Guest',
            'security' => 'nopass',
        ])->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml');
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidWifiData(): array
    {
        return [
            'empty ssid' => [['ssid' => '', 'security' => 'nopass'], 'ssid'],
            'whitespace only ssid' => [['ssid' => '   ', 'security' => 'nopass'], 'ssid'],
            'oversized utf8 ssid' => [['ssid' => str_repeat('ğ', 20), 'security' => 'nopass'], 'ssid'],
            'unsupported security' => [['ssid' => 'Guest', 'security' => 'WEP'], 'security'],
            'missing password' => [['ssid' => 'Guest', 'security' => 'WPA'], 'password'],
            'short password' => [['ssid' => 'Guest', 'security' => 'WPA', 'password' => 'short'], 'password'],
            'overlong password' => [['ssid' => 'Guest', 'security' => 'WPA', 'password' => str_repeat('x', 64)], 'password'],
            'overlong UTF-8 password bytes' => [['ssid' => 'Guest', 'security' => 'WPA', 'password' => str_repeat('ğ', 40)], 'password'],
            'invalid hidden flag' => [['ssid' => 'Guest', 'security' => 'nopass', 'hidden' => 'yes'], 'hidden'],
        ];
    }

    /** @param array<string, mixed> $input */
    #[DataProvider('invalidWifiData')]
    public function test_invalid_wifi_data_is_rejected(array $input, string $field): void
    {
        $this->postJson('/wifi-qr/generate', $input)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    }
}
