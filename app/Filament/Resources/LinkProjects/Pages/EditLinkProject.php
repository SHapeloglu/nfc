<?php
// Bağlantı düzenleme sayfası. Kim çağırır: LinkProjectResource::getPages() ('edit'). Üst çubukta silme işlemi bulunur.

namespace App\Filament\Resources\LinkProjects\Pages;

use App\Filament\Resources\LinkProjects\LinkProjectResource;
use App\Models\LinkProject;
use App\Models\NfcCard;
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
        $record = $this->getRecord();

        if (
            ($record->type !== $data['type'] || $record->target_url !== $data['target_url'])
            && NfcCard::query()
                ->where('link_project_id', $record->getKey())
                ->where(function ($query): void {
                    $query->whereIn('status', ['written', 'verified', 'delivered'])
                        ->orWhereNotNull('written_at')
                        ->orWhereNotNull('verified_at')
                        ->orWhereNotNull('delivered_at');
                })
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'target_url' => 'Bu bağlantı yazılmış bir NFC karta bağlı. Önce kartı güvenli şekilde yeniden hazırlayın.',
            ]);
        }

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
