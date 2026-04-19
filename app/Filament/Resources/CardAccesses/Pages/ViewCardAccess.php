<?php

namespace App\Filament\Resources\CardAccesses\Pages;

use App\Filament\Resources\CardAccesses\CardAccessResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCardAccess extends ViewRecord
{
    protected static string $resource = CardAccessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
