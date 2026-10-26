<?php
// GENERATOR_STUDIO_LINK_MANAGEMENT_V1
// Bağlantı ekleme/düzenleme formu. Kim çağırır: LinkProjectResource::form().
// Hedef adres doğrulaması LinkProjectController ile aynı kuralları izler; iki yer birlikte güncellenmelidir.

namespace App\Filament\Resources\LinkProjects\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Closure;

/**
 * Bağlantı formunun alanları ve hedef adres doğrulaması.
 */
class LinkProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Bağlantı Adı')
                    ->placeholder('Örn: OLAP Teknoloji')
                    ->required()
                    ->maxLength(150),

                Select::make('type')
                    ->label('Bağlantı Türü')
                    ->options([
                        'google_review' => 'Google Yorum',
                        'instagram_follow' => 'Instagram Takip',
                    ])
                    ->required()
                    // Tür değişince hedef adres doğrulaması $get('type') ile yeniden çalışsın diye canlı güncellenir.
                    ->live(),

                TextInput::make('target_url')
                    ->label('Hedef Bağlantı')
                    ->placeholder('https://...')
                    ->required()
                    ->url()
                    ->maxLength(2048)
                    ->rules([
                        fn (Get $get): Closure =>
                            function (
                                string $attribute,
                                mixed $value,
                                Closure $fail
                            ) use ($get): void {
                                // Doğrulama türe bağlıdır; ayrıştırılamayan adres hemen reddedilir.
                                $url = parse_url((string) $value);

                                if (!is_array($url)) {
                                    $fail('Geçerli bir bağlantı girin.');
                                    return;
                                }

                                $scheme = strtolower(
                                    $url['scheme'] ?? ''
                                );

                                $host = strtolower(
                                    $url['host'] ?? ''
                                );

                                // Yalnız https; adrese gömülü kimlik bilgisi ve port reddedilir.
                                if (
                                    $scheme !== 'https' ||
                                    isset($url['user']) ||
                                    isset($url['pass']) ||
                                    isset($url['port'])
                                ) {
                                    $fail('Yalnızca HTTPS bağlantısı kabul edilir.');
                                    return;
                                }

                                $type = $get('type');

                                // Google: yalnız search.google.com yorum yazma yolu ve geçerli placeid kabul edilir.
                                if ($type === 'google_review') {
                                    parse_str(
                                        $url['query'] ?? '',
                                        $params
                                    );

                                    if (
                                        $host !== 'search.google.com' ||
                                        ($url['path'] ?? '') !==
                                            '/local/writereview' ||
                                        !is_string(
                                            $params['placeid'] ?? null
                                        ) ||
                                        !preg_match(
                                            '/^[A-Za-z0-9_-]{10,200}$/',
                                            $params['placeid']
                                        )
                                    ) {
                                        $fail(
                                            'Geçerli bir Google yorum bağlantısı girin.'
                                        );
                                    }
                                // Instagram: profil kullanıcı adı ve xtok paylaşım jetonu zorunludur.
                                } elseif ($type === 'instagram_follow') {
                                    parse_str(
                                        $url['query'] ?? '',
                                        $params
                                    );

                                    $username = trim(
                                        $url['path'] ?? '',
                                        '/'
                                    );

                                    if (
                                        !in_array(
                                            $host,
                                            ['instagram.com', 'www.instagram.com'],
                                            true
                                        ) ||
                                        !preg_match(
                                            '/^[A-Za-z0-9._]{1,30}$/',
                                            $username
                                        ) ||
                                        !is_string(
                                            $params['xtok'] ?? null
                                        ) ||
                                        !preg_match(
                                            '/^[A-Za-z0-9_+\/=-]{8,256}$/',
                                            $params['xtok']
                                        )
                                    ) {
                                        $fail(
                                            'Instagram profil paylaşım bağlantısı girin.'
                                        );
                                    }
                                }
                            },
                    ]),
            ]);
    }
}
