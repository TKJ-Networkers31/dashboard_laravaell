<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Services\Lab\LabService;

class KelembabanChart extends ChartWidget
{
    protected static ?int $sort = 4;
    protected ?string $pollingInterval = '5s';
    protected ?string $heading = 'Grafik Kelembaban';

    protected function getData(): array
    {
        $data = app(LabService::class)->getAll();
        $chart = collect($data['chart']);

        return [
            'datasets' => [
                [
                    'label' => 'Kelembapan',
                    'data' => $chart->pluck('kelembapan'),
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
