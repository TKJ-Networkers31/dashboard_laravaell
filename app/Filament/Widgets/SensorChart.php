<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Services\Lab\LabService;
use Carbon\Carbon;

class SensorChart extends ChartWidget
{
    protected static ?int $sort = 4;
    protected ?string $pollingInterval = '5s';
    protected ?string $heading = 'Grafik Suhu (°C)';

    protected function getData(): array
    {
        $data  = app(LabService::class)->getAll();
        $chart = collect($data['chart'] ?? []);

        $suhuData  = $chart->pluck('suhu')->map(fn ($v) => round((float) $v, 1))->toArray();
        $labels    = $chart->map(function ($item) {
            $ts = $item['record_at'] ?? null;
            return $ts ? Carbon::parse($ts)->setTimezone('Asia/Jakarta')->format('H:i:s') : '-';
        })->toArray();

        return [
            'datasets' => [
                [
                    'label'           => 'Suhu (°C)',
                    'data'            => $suhuData,
                    'borderColor'     => 'rgb(239,68,68)',
                    'backgroundColor' => 'rgba(239,68,68,0.1)',
                    'fill'            => true,
                    'tension'         => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
