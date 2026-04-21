<?php

namespace App\Filament\Widgets;

use App\Models\LogSensor;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class SensorTable extends TableWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 5;
    // protected ?string $pollingInterval = '5s';
    public function table(Table $table): Table
    {

        return $table
        ->query(fn (): Builder =>
            LogSensor::query()->latest('record_at')
        )
        ->paginated([50])
        ->columns([
            TextColumn::make('device')->searchable(),

            TextColumn::make('rssi')->searchable(),

            TextColumn::make('suhu')
                ->numeric()
                ->sortable(),

            TextColumn::make('kelembapan')
                ->numeric()
                ->sortable(),

            TextColumn::make('cahaya')
                ->numeric()
                ->sortable(),

            TextColumn::make('jarak_objek')
                ->numeric()
                ->sortable(),

            TextColumn::make('sisa_memori')
                ->numeric()
                ->sortable(),

            TextColumn::make('max_size_memori')
                ->numeric()
                ->sortable(),

            TextColumn::make('record_at')
                ->dateTime()
                ->sortable(),
        ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
