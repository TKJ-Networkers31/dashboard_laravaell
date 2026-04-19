<?php

namespace App\Filament\Resources\CardAccesses\Pages;

use App\Filament\Resources\CardAccesses\CardAccessResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCardAccess extends EditRecord
{
    protected static string $resource = CardAccessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
