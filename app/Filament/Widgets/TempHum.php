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
        $data   = app(LabService::class)->getAll();
        $latest = $data['latest'];
        $chart  = collect($data['chart']);

        if (empty($latest)) {
            return [
                Stat::make('Status', 'Menunggu data sensor...')
                    ->description('MQTT belum menerima data')
                    ->color('gray'),
            ];
        }

        // DB menyimpan sebagai 'suhu' dan 'kelembapan'
        $suhu      = round((float) ($latest['suhu']      ?? 0), 1);
        $kelembapan= round((float) ($latest['kelembapan'] ?? 0), 1);

        $suhuChart      = $chart->pluck('suhu')->map(fn ($v) => (float) $v)->toArray();
        $kelembapanChart= $chart->pluck('kelembapan')->map(fn ($v) => (float) $v)->toArray();

        return [
            Stat::make('Suhu Ruangan', $suhu . ' °C')
                ->description($suhu > 30 ? '🔴 Panas — di atas normal' : '🟢 Normal')
                ->chart($suhuChart)
                ->color($suhu > 30 ? 'danger' : 'success'),

            Stat::make('Kelembapan', $kelembapan . ' %')
                ->description(
                    $kelembapan > 80
                        ? '💧 Sangat lembap'
                        : ($kelembapan < 40 ? '🌵 Terlalu kering' : '🟢 Kondisi ideal')
                )
                ->chart($kelembapanChart)
                ->color(
                    ($kelembapan > 80 || $kelembapan < 40) ? 'warning' : 'info'
                ),
        ];
    }

    protected function getColumns(): int
    {
        return 2;
    }
}
