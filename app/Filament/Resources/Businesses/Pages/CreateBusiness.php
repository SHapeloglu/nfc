<?php

namespace App\Filament\Resources\Businesses\Pages;

use App\Filament\Resources\Businesses\BusinessResource;
use App\Models\Business;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBusiness extends CreateRecord
{
    protected static string $resource = BusinessResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $business = new Business($data);
        $business->user_id = auth()->id();
        $business->save();

        return $business;
    }
}
