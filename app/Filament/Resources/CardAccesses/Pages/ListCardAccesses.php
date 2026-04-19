<?php

namespace App\Filament\Resources\CardAccesses\Pages;

use App\Filament\Resources\CardAccesses\CardAccessResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCardAccesses extends ListRecords
{
    protected static string $resource = CardAccessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
