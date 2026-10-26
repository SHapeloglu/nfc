<?php
// Bağlantı düzenleme sayfası. Kim çağırır: LinkProjectResource::getPages() ('edit'). Üst çubukta silme işlemi bulunur.

namespace App\Filament\Resources\LinkProjects\Pages;

use App\Filament\Resources\LinkProjects\LinkProjectResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Bağlantı düzenleme sayfası.
 */
class EditLinkProject extends EditRecord
{
    protected static string $resource = LinkProjectResource::class;

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
