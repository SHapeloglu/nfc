<?php

namespace App\Http\Controllers;

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class WifiQrController extends Controller
{
    public function index(): View
    {
        return view('generators.wifi-qr');
    }

    public function generate(Request $request): Response
    {
        $data = $request->validate([
            'ssid' => ['required', 'string', 'max:32'],
            'security' => ['required', Rule::in(['WPA', 'nopass'])],
            'password' => ['required_if:security,WPA', 'nullable', 'string', 'max:63'],
            'hidden' => ['sometimes', 'boolean'],
        ]);

        // SSID limit is measured in UTF-8 bytes, not characters.
        if (strlen($data['ssid']) > 32) {
            throw ValidationException::withMessages([
                'ssid' => 'WiFi ağ adı en fazla 32 bayt olabilir.',
            ]);
        }

        if ($data['security'] === 'WPA' && mb_strlen($data['password'] ?? '') < 8) {
            throw ValidationException::withMessages([
                'password' => 'WiFi şifresi en az 8 karakter olmalıdır.',
            ]);
        }

        $escape = static fn (string $value): string => str_replace(
            ['\\', ';', ',', ':', '"'],
            ['\\\\', '\\;', '\\,', '\\:', '\\"'],
            $value,
        );

        $payload = 'WIFI:T:'.$data['security']
            .';S:'.$escape($data['ssid'])
            .';P:'.($data['security'] === 'WPA' ? $escape($data['password']) : '')
            .';H:'.(!empty($data['hidden']) ? 'true' : 'false')
            .';;';

        $qr = new QrCode(
            data: $payload,
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 500,
            margin: 20,
        );

        $result = (new SvgWriter())->write($qr);

        return response($result->getString(), 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'inline; filename="generator-studio-wifi.svg"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
