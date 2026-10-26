<?php

namespace App\Filament\Resources\NfcCards;

use App\Filament\Resources\NfcCards\Pages\CreateNfcCard;
use App\Filament\Resources\NfcCards\Pages\EditNfcCard;
use App\Filament\Resources\NfcCards\Pages\ListNfcCards;
use App\Models\NfcCard;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
        return static::canViewAny() && $record->user_id === auth()->id();
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
            TextInput::make('tag_uid')->label('Kart UID (varsa)')->maxLength(128),
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

    public static function getPages(): array
    {
        return [
            'index' => ListNfcCards::route('/'),
            'create' => CreateNfcCard::route('/create'),
            'edit' => EditNfcCard::route('/{record}/edit'),
        ];
    }
}
