<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Services\Lab\LabService;
use Carbon\Carbon;

class KelembabanChart extends ChartWidget
{
    protected static ?int $sort = 5; // berbeda dari SensorChart (sort=4)
    protected ?string $pollingInterval = '5s';
    protected ?string $heading = 'Grafik Kelembapan (%)';

    protected function getData(): array
    {
        $data  = app(LabService::class)->getAll();
        $chart = collect($data['chart'] ?? []);

        $kelembapanData = $chart->pluck('kelembapan')->map(fn ($v) => round((float) $v, 1))->toArray();
        $labels         = $chart->map(function ($item) {
            $ts = $item['record_at'] ?? null;
            return $ts ? Carbon::parse($ts)->setTimezone('Asia/Jakarta')->format('H:i:s') : '-';
        })->toArray();

        return [
            'datasets' => [
                [
                    'label'           => 'Kelembapan (%)',
                    'data'            => $kelembapanData,
                    'borderColor'     => 'rgb(59,130,246)',
                    'backgroundColor' => 'rgba(59,130,246,0.1)',
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
