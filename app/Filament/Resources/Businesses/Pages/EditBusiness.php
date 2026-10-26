<?php

namespace App\Filament\Resources\Businesses\Pages;

use App\Filament\Resources\Businesses\BusinessResource;
use App\Models\Business;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditBusiness extends EditRecord
{
    protected static string $resource = BusinessResource::class;

    /**
     * Sahipli kart varsa silme butonu gizlenir; yerine ipucu gösterilir.
     */
    protected function getHeaderActions(): array
    {
        $record = $this->getRecord();

        return [
            DeleteAction::make()
                ->visible(fn (Business $record): bool => BusinessResource::canDelete($record)),
            Action::make('silmeIpucu')
                ->label('Neden silinemiyor?')
                ->icon(Heroicon::OutlinedInformationCircle)
                ->color('gray')
                ->visible(fn (): bool => BusinessResource::canEdit($record) && BusinessResource::hasCards($record))
                ->requiresConfirmation()
                ->modalHeading('İşletme silinemiyor')
                ->modalDescription('Bu işletmeye bağlı NFC kartlar var. Önce kartları NFC Kartlar listesinden "Yeniden hazırla" ile sıfırlayıp, sonra işletmeyi silin.')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tamam'),
        ];
    }
}
