<?php

namespace App\Filament\Widgets;

use App\Models\LogSensor;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class SensorTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';
    protected static ?int $sort = 6;
    protected ?string $pollingInterval = '5s';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => LogSensor::query()->latest('record_at'))
            ->paginated([25, 50, 100])
            ->columns([
                TextColumn::make('device')
                    ->label('Perangkat')
                    ->searchable(),
                TextColumn::make('suhu')
                    ->label('Suhu (°C)')
                    ->numeric(decimalPlaces: 1)
                    ->sortable(),
                TextColumn::make('kelembapan')
                    ->label('Kelembapan (%)')
                    ->numeric(decimalPlaces: 1)
                    ->sortable(),
                TextColumn::make('cahaya')
                    ->label('Cahaya (lux)')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('jarak_objek')
                    ->label('Jarak (cm)')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('rssi')
                    ->label('RSSI (dBm)'),
                TextColumn::make('sisa_memori')
                    ->label('Free RAM (KB)')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('record_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i:s')
                    ->timezone('Asia/Jakarta')
                    ->sortable(),
            ]);
    }
}
