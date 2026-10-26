<?php

namespace App\Filament\Resources\NfcCards;

use App\Filament\Resources\NfcCards\Pages\CreateNfcCard;
use App\Filament\Resources\NfcCards\Pages\EditNfcCard;
use App\Filament\Resources\NfcCards\Pages\ListNfcCards;
use App\Models\NfcCard;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class NfcCardResource extends Resource
{
    protected static ?string $model = NfcCard::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;
    protected static ?string $navigationLabel = 'NFC Kartlar';
    protected static ?string $modelLabel = 'NFC Kart';
    protected static ?string $recordTitleAttribute = 'label';
    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->is_admin;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        // Once a card has been written, changing its business/project/UID here
        // would make inventory disagree with the physical tag.
        return static::canViewAny()
            && $record->user_id === auth()->id()
            && in_array($record->status, ['pending', 'failed'], true)
            && $record->written_at === null
            && $record->verified_at === null
            && $record->delivered_at === null;
    }

    public static function canDelete(Model $record): bool
    {
        return static::canEdit($record)
            && $record->status === 'pending'
            && $record->written_at === null
            && $record->verified_at === null
            && $record->delivered_at === null;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    /**
     * Yazılmış/doğrulanmış/hatalı kart sahibi tarafından "Yeniden hazırla" ile pending'e döndürülebilir.
     * Teslim edilmiş kart sıfırlanamaz: fiziksel olarak müşteriye ulaşmış bir etiketin kaydı değiştirilmemeli.
     */
    public static function canReset(Model $record): bool
    {
        return static::canViewAny()
            && $record->user_id === auth()->id()
            && in_array($record->status, ['written', 'verified', 'failed'], true);
    }

    /**
     * Yalnızca sahibinin doğrulanmış kartı teslim edildi olarak işaretlenebilir.
     */
    public static function canMarkDelivered(Model $record): bool
    {
        return static::canViewAny()
            && $record->user_id === auth()->id()
            && $record->status === 'verified';
    }

    /**
     * Yazım öncesi/yazım sonrası hata: sahibi, henüz doğrulanmamış kartı hatalı işaretleyebilir.
     */
    public static function canMarkFailed(Model $record): bool
    {
        return static::canViewAny()
            && $record->user_id === auth()->id()
            && in_array($record->status, ['pending', 'written'], true);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('label')->label('Kart Etiketi')->required()->maxLength(150),
            Select::make('business_id')
                ->label('İşletme')
                ->relationship('business', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->where('user_id', auth()->id()))
                ->rules([Rule::exists('businesses', 'id')->where('user_id', auth()->id())])
                ->searchable()->preload()->required(),
            Select::make('link_project_id')
                ->label('Bağlantı Projesi')
                ->relationship('linkProject', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->where('user_id', auth()->id()))
                ->rules([Rule::exists('link_projects', 'id')->where('user_id', auth()->id())])
                ->searchable()->preload(),
            TextInput::make('tag_uid')
                ->label('Kart UID (varsa)')
                ->maxLength(128)
                // Aynı kullanıcıda aynı UID iki karta bağlanamaz; boş değer serbesttir.
                ->rules([
                    fn (?Model $record) => Rule::unique('nfc_cards', 'tag_uid')
                        ->where('user_id', auth()->id())
                        ->ignore($record?->getKey()),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('label')->label('Kart')->searchable()->sortable(),
            TextColumn::make('business.name')->label('İşletme')->searchable(),
            TextColumn::make('linkProject.name')->label('Bağlantı'),
            TextColumn::make('status')->label('Durum')->badge(),
            TextColumn::make('verified_at')->label('Doğrulama')->dateTime('d.m.Y H:i'),
            TextColumn::make('delivered_at')->label('Teslim')->dateTime('d.m.Y H:i'),
        ])->recordActions([
            Action::make('reset')
                ->label('Yeniden hazırla')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('warning')
                ->visible(fn (NfcCard $record): bool => static::canReset($record))
                ->requiresConfirmation()
                ->modalHeading('Kart yeniden hazırlansın mı?')
                ->modalDescription('Kart "Hazırlanacak" durumuna döner; yazım, doğrulama ve UID bilgileri temizlenir. Fiziksel karta yeniden yazmanız gerekir.')
                ->action(fn (NfcCard $record) => static::transition(
                    $record,
                    static::canReset($record),
                    [
                        'status' => 'pending',
                        'written_url' => null,
                        'written_at' => null,
                        'verified_at' => null,
                        'delivered_at' => null,
                        // UID sıfırlanır: yeniden hazırlanan kart kayıtlı UID'siyle değil, fiziksel etiket yeniden okunarak eşleştirilmeli.
                        'tag_uid' => null,
                    ],
                    'Kart yeniden hazırlanacak durumda.',
                )),
            Action::make('markDelivered')
                ->label('Teslim edildi')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->visible(fn (NfcCard $record): bool => static::canMarkDelivered($record))
                ->action(fn (NfcCard $record) => static::transition(
                    $record,
                    static::canMarkDelivered($record),
                    ['status' => 'delivered', 'delivered_at' => now()],
                    'Kart teslim edildi olarak işaretlendi.',
                )),
            Action::make('markFailed')
                ->label('Hatalı işaretle')
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color('danger')
                ->visible(fn (NfcCard $record): bool => static::canMarkFailed($record))
                ->action(fn (NfcCard $record) => static::transition(
                    $record,
                    static::canMarkFailed($record),
                    ['status' => 'failed'],
                    'Kart hatalı olarak işaretlendi.',
                )),
        ])->filters([
            SelectFilter::make('status')->label('Durum')->options([
                'pending' => 'Hazırlanacak',
                'written' => 'Yazıldı',
                'verified' => 'Doğrulandı',
                'delivered' => 'Teslim Edildi',
                'failed' => 'Hatalı',
            ]),
        ])->defaultSort('created_at', 'desc');
    }

    /**
     * Durum geçişini tek yerden uygular. Yetki kontrolü eylem anında yeniden yapılır (görünürlük yetmez).
     * forceFill: 'status' ve zaman damgaları $fillable dışında kalsa bile geçiş bozulmaz.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected static function transition(NfcCard $record, bool $allowed, array $attributes, string $successMessage): void
    {
        if (! $allowed) {
            Notification::make()->danger()->title('Bu kart için bu işlem yapılamaz.')->send();

            return;
        }

        $record->forceFill($attributes)->save();

        Notification::make()->success()->title($successMessage)->send();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNfcCards::route('/'),
            'create' => CreateNfcCard::route('/create'),
            'edit' => EditNfcCard::route('/{record}/edit'),
        ];
    }
}
