<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\LogSensor;
use Carbon\Carbon;

class SensorChart extends ChartWidget
{
    // protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 3;
    protected ?string $pollingInterval = '5s';

    // protected int | string | array $columnSpan = 1;

    protected ?string $heading = 'Grafik Suhu';

    protected function getData(): array
    {
        $data = LogSensor::latest('record_at')->take(10)->get()->reverse();

        return [
            'datasets' => [
                [
                    'label' => 'Suhu',
                    'data' => $data->pluck('suhu'),
                ],
            ],
            'labels' => $data->pluck('record_at')->map(function ($time) {
                // return $time->format('H:i:s');
                return Carbon::parse($time)->format('H:i:s');
            }),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
