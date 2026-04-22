<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Services\Lab\LabService;

class TempHum extends StatsOverviewWidget
{
    protected static ?int $sort = 3;
    protected ?string $pollingInterval = '5s';

    protected function getStats(): array
    {
        $data = app(LabService::class)->getAll();
        $latest = $data['latest'];
        $chart = collect($data['chart']);

        if (empty($latest)) {
            return [Stat::make('Status', 'Menunggu data...')->color('gray')];
        }

        return [
            Stat::make('Suhu Ruangan', $latest['suhu'] . '°C')
                ->description($latest['suhu'] > 30 ? 'Panas' : 'Normal')
                ->chart($chart->pluck('suhu')->toArray())
                ->color($latest['suhu'] > 30 ? 'danger' : 'success'),

            Stat::make('Kelembapan', $latest['kelembapan'] . '%')
                ->description('Kondisi udara lab')
                ->chart($chart->pluck('kelembapan')->toArray())
                ->color('info'),
        ];
    }

    protected function getColumns(): int { return 2; }
}
