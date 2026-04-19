<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\LogSensor;
use App\Models\DeviceState;

class StatsOverview extends StatsOverviewWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 1;
    protected ?string $pollingInterval = '5s';


    protected function getStats(): array
    {

        $latest1 = LogSensor::latest('record_at')->first();
        $latest2 = DeviceState::where('device','esp32_smartlab_1')->first();
        return [
            Stat::make('Suhu', $latest1->suhu . ' °C')
                ->description('Data terakhir')
                ->color('danger'),

            Stat::make('Kelembaban', $latest1->kelembapan . ' %')
                ->color('info'),

            Stat::make('Status Lampu 1_2', $latest2->lampu1_2 ? 'ON' : 'OFF')
                ->color($latest2->lampu1_2 ? 'success' : 'gray'),
            Stat::make('Status Lampu 3_4', $latest2->lampu3_4 ? 'ON' : 'OFF')
                ->color($latest2->lampu3_4 ? 'success' : 'gray'),
        ];
    }
}
