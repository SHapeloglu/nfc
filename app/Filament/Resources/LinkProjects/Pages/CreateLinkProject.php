<?php
// GENERATOR_STUDIO_LINK_MANAGEMENT_V1
// Yeni bağlantı kaydı sayfası. Kim çağırır: LinkProjectResource::getPages() ('create').

namespace App\Filament\Resources\LinkProjects\Pages;

use App\Filament\Resources\LinkProjects\LinkProjectResource;
use App\Models\LinkProject;
use App\Services\LinkTargetService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

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
        $existing = LinkProject::query()
            ->where('user_id', auth()->id())
            ->where('type', $data['type'])
            ->where('target_url', $data['target_url'])
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'target_url' => 'Bu bağlantı zaten kayıtlı.',
            ]);
        }

        $data['metadata'] = app(LinkTargetService::class)->metadataFor(
            $data['type'],
            $data['target_url']
        );

        $record = new LinkProject($data);

        $record->user_id = auth()->id();

        $record->save();

        return $record;
    }
}
