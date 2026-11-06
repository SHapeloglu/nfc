<?php

namespace App\Filament\Resources\NfcCards\Pages;

use App\Filament\Resources\NfcCards\NfcCardResource;
use App\Models\NfcCard;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateNfcCard extends CreateRecord
{
    protected static string $resource = NfcCardResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $card = new NfcCard($data);
        $card->user_id = auth()->id();
        $card->business_id = $data['business_id'];
        $card->link_project_id = $data['link_project_id'] ?? null;
        $card->save();

        return $card;
    }
}
