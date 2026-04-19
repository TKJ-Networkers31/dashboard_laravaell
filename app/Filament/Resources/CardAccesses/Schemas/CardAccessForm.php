<?php

namespace App\Filament\Resources\CardAccesses\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CardAccessForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('pengguna')
                    ->required(),
                TextInput::make('UID')
                    ->required(),
                TextInput::make('kelas')
                    ->required(),
                TextInput::make('jurusan')
                    ->required(),
            ]);
    }
}
