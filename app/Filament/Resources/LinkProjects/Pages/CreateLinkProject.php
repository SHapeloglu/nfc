<?php
// GENERATOR_STUDIO_LINK_MANAGEMENT_V1
// Yeni bağlantı kaydı sayfası. Kim çağırır: LinkProjectResource::getPages() ('create').

namespace App\Filament\Resources\LinkProjects\Pages;

use App\Filament\Resources\LinkProjects\LinkProjectResource;
use App\Models\LinkProject;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * Bağlantı oluşturma sayfası.
 */
class CreateLinkProject extends CreateRecord
{
    protected static string $resource = LinkProjectResource::class;

    /**
     * Kaydı oluşturur. user_id $fillable dışında olduğu için form verisinden gelmez; oturumdaki kullanıcıdan elle atanır.
     */
    protected function handleRecordCreation(array $data): Model
    {
        $record = new LinkProject($data);

        $record->user_id = auth()->id();

        $record->save();

        return $record;
    }
}
