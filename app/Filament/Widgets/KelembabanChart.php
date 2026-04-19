<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\LogSensor;
use Carbon\Carbon;

class KelembabanChart extends ChartWidget
{
    protected static ?int $sort = 4;
    protected ?string $pollingInterval = '5s';

    // protected int | string | array $columnSpan = 1;
    // protected int | string | array $columnSpan = 'full';

    protected ?string $heading = 'Kelembaban Chart';

    protected function getData(): array
    {
        $data = LogSensor::latest('record_at')->take(10)->get()->reverse();
        return [
            'datasets' => [
                [
                    'label' => 'kelembapan',
                    'data' => $data->pluck('kelembapan'),
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
