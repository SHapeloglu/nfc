<?php

namespace App\Filament\Resources\NfcCards\Pages;

use App\Filament\Resources\NfcCards\NfcCardResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNfcCards extends ListRecords
{
    protected static string $resource = NfcCardResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
