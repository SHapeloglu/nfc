<?php
// GENERATOR_STUDIO_LINK_MANAGEMENT_V1
// Filament 'Bağlantı Yönetimi' kaynağı: kayıtlı bağlantıları listeler, ekler, düzenler. Yalnız yönetici (is_admin) görür.
// Form: Schemas/LinkProjectForm, tablo: Tables/LinkProjectsTable, sayfalar: Pages/*. Model: LinkProject.

namespace App\Filament\Resources\LinkProjects;

use App\Filament\Resources\LinkProjects\Pages\CreateLinkProject;
use App\Filament\Resources\LinkProjects\Pages\EditLinkProject;
use App\Filament\Resources\LinkProjects\Pages\ListLinkProjects;
use App\Filament\Resources\LinkProjects\Schemas\LinkProjectForm;
use App\Filament\Resources\LinkProjects\Tables\LinkProjectsTable;
use App\Models\LinkProject;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Bağlantı kaynağı: erişim yönetici ile sınırlıdır ve her kullanıcı yalnız kendi kayıtlarını görür/düzenler.
 */
class LinkProjectResource extends Resource
{
    protected static ?string $model = LinkProject::class;

    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedLink;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Bağlantı Yönetimi';

    protected static ?string $modelLabel = 'Bağlantı';

    protected static ?string $pluralModelLabel = 'Bağlantılar';

    protected static ?int $navigationSort = 2;

    /**
     * Menüde ve listede görünürlük: yalnız is_admin olan kullanıcı.
     */
    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->is_admin;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    /**
     * Düzenleme: yönetici olmalı ve kayıt kendisine ait olmalı.
     */
    public static function canEdit(Model $record): bool
    {
        return static::canViewAny()
            && $record->user_id === auth()->id();
    }

    public static function canDelete(Model $record): bool
    {
        // Deletion would NULL nfc_cards.link_project_id and orphan assigned tags.
        // Bağlı kart varsa silinemez; önce kartlar "Yeniden hazırla" ile sıfırlanmalı.
        return static::canEdit($record)
            && ! static::hasCards($record);
    }

    /**
     * Tablo sorgusu withExists('nfcCards as has_cards') ile besleniyorsa sorgu atılmaz (satır başına N+1 önlenir).
     */
    public static function hasCards(Model $record): bool
    {
        return (bool) ($record->has_cards ?? $record->nfcCards()->exists());
    }

    /**
     * Toplu silme kapalıdır; yalnız tek tek silinebilir (canDelete).
     */
    public static function canDeleteAny(): bool
    {
        return false;
    }

    /**
     * Sorgu oturumdaki kullanıcının kayıtlarıyla sınırlanır; başkasının bağlantıları listelenmez.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id())
            ->withExists('nfcCards as has_cards');
    }

    public static function form(Schema $schema): Schema
    {
        return LinkProjectForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LinkProjectsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLinkProjects::route('/'),
            'create' => CreateLinkProject::route('/create'),
            'edit' => EditLinkProject::route('/{record}/edit'),
        ];
    }
}
