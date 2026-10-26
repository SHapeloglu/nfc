<?php
// Bağlantı düzenleme sayfası. Kim çağırır: LinkProjectResource::getPages() ('edit'). Üst çubukta silme işlemi bulunur.

namespace App\Filament\Resources\LinkProjects\Pages;

use App\Filament\Resources\LinkProjects\LinkProjectResource;
use App\Models\LinkProject;
use App\Models\NfcCard;
use App\Services\LinkTargetService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Support\Icons\Heroicon;
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
                'data.target_url' => 'Bu bağlantı yazılmış bir NFC karta bağlı. Önce kartı NFC Kartlar listesinden "Yeniden hazırla" ile sıfırlayın (teslim edilmiş kartlar sıfırlanamaz).',
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
                'data.target_url' => 'Bu bağlantı zaten kayıtlı.',
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
     * Bağlı kart varsa silme butonu gizlenir (canDelete); yerine neden silinemediğini anlatan ipucu gösterilir.
     */
    protected function getHeaderActions(): array
    {
        $record = $this->getRecord();

        return [
            DeleteAction::make()
                ->visible(fn (LinkProject $record): bool => LinkProjectResource::canDelete($record)),
            Action::make('silmeIpucu')
                ->label('Neden silinemiyor?')
                ->icon(Heroicon::OutlinedInformationCircle)
                ->color('gray')
                ->visible(fn (): bool => LinkProjectResource::canEdit($record) && LinkProjectResource::hasCards($record))
                ->requiresConfirmation()
                ->modalHeading('Bağlantı silinemiyor')
                ->modalDescription('Bu bağlantı bir veya daha fazla NFC kartına bağlı. Önce bağlı kartları NFC Kartlar listesinden "Yeniden hazırla" ile sıfırlayıp, sonra bağlantıyı silin.')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tamam'),
        ];
    }
}
