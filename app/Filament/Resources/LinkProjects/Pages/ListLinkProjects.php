<?php
// Bağlantı listesi sayfası. Kim çağırır: LinkProjectResource::getPages() ('index'). Üst çubukta 'oluştur' işlemi bulunur.

namespace App\Filament\Resources\LinkProjects\Pages;

use App\Filament\Resources\LinkProjects\LinkProjectResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * Bağlantı listesi sayfası.
 */
class ListLinkProjects extends ListRecords
{
    protected static string $resource = LinkProjectResource::class;

    /**
     * Sayfa başlığındaki işlemler: yeni bağlantı oluşturma.
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
