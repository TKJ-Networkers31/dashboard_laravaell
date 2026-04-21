<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Services\Lab\LabService;
use Carbon\Carbon;

class SensorChart extends ChartWidget
{
    // protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 4;
    // protected ?string $pollingInterval = '5s';

    // protected int | string | array $columnSpan = 1;

    protected ?string $heading = 'Grafik Suhu';

    protected function getData(): array
    {
        $data = app(\App\Services\Lab\LabService::class)->getAll();
        $chart = collect($data['chart'] ?? []);

        return [
            'datasets' => [
                [
                    'label' => 'Suhu',
                    'data' => $chart->pluck('suhu'),
                ],
            ],
            'labels' => $chart->pluck('record_at')->map(
                fn ($t) => \Carbon\Carbon::parse($t)->format('H:i:s')
            ),
        ];
    }



    protected function getType(): string
    {
        return 'line';
    }
}
