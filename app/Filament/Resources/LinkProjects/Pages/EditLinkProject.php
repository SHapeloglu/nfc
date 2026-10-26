<?php
// Bağlantı düzenleme sayfası. Kim çağırır: LinkProjectResource::getPages() ('edit'). Üst çubukta silme işlemi bulunur.

namespace App\Filament\Resources\LinkProjects\Pages;

use App\Filament\Resources\LinkProjects\LinkProjectResource;
use App\Models\LinkProject;
use App\Services\LinkTargetService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

/**
 * Bağlantı düzenleme sayfası.
 */
class EditLinkProject extends EditRecord
{
    protected static string $resource = LinkProjectResource::class;

    /**
     * Keep derived metadata in sync after the destination is edited.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $duplicate = LinkProject::query()
            ->where('user_id', auth()->id())
            ->where('type', $data['type'])
            ->where('target_url', $data['target_url'])
            ->whereKeyNot($this->getRecord()->getKey())
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'target_url' => 'Bu bağlantı zaten kayıtlı.',
            ]);
        }

        $data['metadata'] = app(LinkTargetService::class)->metadataFor(
            $data['type'],
            $data['target_url']
        );

        return $data;
    }

    /**
     * Sayfa başlığındaki işlemler: tek kayıt silme.
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
