<?php
// GENERATOR_STUDIO_LINK_MANAGEMENT_V1
// Bağlantı ekleme/düzenleme formu. Kim çağırır: LinkProjectResource::form().
// Hedef adres doğrulaması LinkProjectController ile aynı kuralları izler; iki yer birlikte güncellenmelidir.

namespace App\Filament\Resources\LinkProjects\Schemas;

use App\Services\LinkTargetService;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;

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
                        fn (Get $get): Closure => function (
                            string $attribute,
                            mixed $value,
                            Closure $fail
                        ) use ($get): void {
                            try {
                                app(LinkTargetService::class)->metadataFor(
                                    (string) $get('type'),
                                    (string) $value
                                );
                            } catch (ValidationException $exception) {
                                $fail($exception->errors()['target_url'][0]);
                            }
                        },
                    ]),
            ]);
    }
}
