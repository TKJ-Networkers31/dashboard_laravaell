<?php

namespace App\Filament\Resources\CardAccesses;

use App\Filament\Resources\CardAccesses\Pages\CreateCardAccess;
use App\Filament\Resources\CardAccesses\Pages\EditCardAccess;
use App\Filament\Resources\CardAccesses\Pages\ListCardAccesses;
use App\Filament\Resources\CardAccesses\Pages\ViewCardAccess;
use App\Filament\Resources\CardAccesses\Schemas\CardAccessForm;
use App\Filament\Resources\CardAccesses\Schemas\CardAccessInfolist;
use App\Filament\Resources\CardAccesses\Tables\CardAccessesTable;
use App\Models\CardAccess;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CardAccessResource extends Resource
{
    protected static ?string $model = CardAccess::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-lock-open';

    protected static ?string $recordTitleAttribute = 'App\Models\CardAccess';

    public static function form(Schema $schema): Schema
    {
        return CardAccessForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CardAccessInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CardAccessesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCardAccesses::route('/'),
            'create' => CreateCardAccess::route('/create'),
            'view' => ViewCardAccess::route('/{record}'),
            'edit' => EditCardAccess::route('/{record}/edit'),
        ];
    }
}
