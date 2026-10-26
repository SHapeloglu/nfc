<?php
// GENERATOR_STUDIO_LINK_MANAGEMENT_V1
// Bağlantı listesi tablosu: sütunlar, tür filtresi ve satır işlemleri (QR oluştur, düzenle).
// Kim çağırır: LinkProjectResource::table().

namespace App\Filament\Resources\LinkProjects\Tables;

use App\Models\LinkProject;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Bağlantı projeleri tablosunun yapılandırması.
 */
class LinkProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Bağlantı Adı')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Tür')
                    ->formatStateUsing(
                        fn (string $state): string =>
                            match ($state) {
                                'google_review' => 'Google Yorum',
                                'instagram_follow' => 'Instagram Takip',
                                default => $state,
                            }
                    )
                    ->badge(),

                TextColumn::make('target_url')
                    ->label('Bağlantı')
                    ->limit(45)
                    ->copyable(),

                TextColumn::make('created_at')
                    ->label('Oluşturulma')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Bağlantı Türü')
                    ->options([
                        'google_review' => 'Google Yorum',
                        'instagram_follow' => 'Instagram Takip',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('qr')
                    ->label('QR Oluştur')
                    ->icon('heroicon-o-qr-code')
                    // Hedef adres, QR üretici sayfasına 'content' parametresi olarak taşınır; http_build_query kodlamayı yapar.
                    ->url(
                        fn (LinkProject $record): string =>
                            url('/qr') . '?' . http_build_query([
                                'content' => $record->target_url,
                            ])
                    )
                    ->openUrlInNewTab(),

                EditAction::make()
                    ->label('Düzenle'),
            ]);
    }
}
