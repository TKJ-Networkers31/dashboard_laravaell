<?php

namespace App\Filament\Resources\CardAccesses\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CardAccessInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('pengguna'),
                TextEntry::make('UID'),
                TextEntry::make('kelas'),
                TextEntry::make('jurusan'),
            ]);
    }
}
