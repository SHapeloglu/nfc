<?php

namespace App\Filament\Resources\NfcCards\Pages;

use App\Filament\Resources\NfcCards\NfcCardResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditNfcCard extends EditRecord
{
    protected static string $resource = NfcCardResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->fill($data);
        $record->business_id = $data['business_id'];
        $record->link_project_id = $data['link_project_id'] ?? null;
        $record->save();

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
