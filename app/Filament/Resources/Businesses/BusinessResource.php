<?php

namespace App\Filament\Resources\Businesses;

use App\Filament\Resources\Businesses\Pages\CreateBusiness;
use App\Filament\Resources\Businesses\Pages\EditBusiness;
use App\Filament\Resources\Businesses\Pages\ListBusinesses;
use App\Models\Business;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BusinessResource extends Resource
{
    protected static ?string $model = Business::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;
    protected static ?string $navigationLabel = 'İşletmeler';
    protected static ?string $modelLabel = 'İşletme';
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?int $navigationSort = 3;

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

    /**
     * Sahipli kart varsa işletme silinemez; önce kartlar "Yeniden hazırla" ile sıfırlanmalı.
     */
    public static function canDelete(Model $record): bool
    {
        return static::canEdit($record) && ! static::hasCards($record);
    }

    /**
     * Tablo sorgusu withExists('nfcCards as has_cards') ile besleniyorsa sorgu atılmaz.
     */
    public static function hasCards(Model $record): bool
    {
        return (bool) ($record->has_cards ?? $record->nfcCards()->exists());
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id())
            ->withExists('nfcCards as has_cards');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('İşletme Adı')->required()->maxLength(150),
            TextInput::make('contact_name')->label('Yetkili')->maxLength(150),
            TextInput::make('phone')->label('Telefon')->tel()->maxLength(40),
            Textarea::make('notes')->label('Notlar')->maxLength(5000),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('İşletme')->searchable()->sortable(),
            TextColumn::make('contact_name')->label('Yetkili')->searchable(),
            TextColumn::make('phone')->label('Telefon'),
            TextColumn::make('created_at')->label('Eklenme')->dateTime('d.m.Y H:i')->sortable(),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBusinesses::route('/'),
            'create' => CreateBusiness::route('/create'),
            'edit' => EditBusiness::route('/{record}/edit'),
        ];
    }
}
