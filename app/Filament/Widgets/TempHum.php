<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\LogSensor;

class TempHum extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        // ambil data langsung dari DB (lebih stabil daripada service)
        $latest = LogSensor::query()
            ->latest('record_at')
            ->first();

        // fallback jika belum ada data
        if (!$latest) {
            return [
                Stat::make('Status', 'Menunggu data...')
                    ->description('Belum ada data sensor masuk')
                    ->color('gray'),
            ];
        }

        // ambil chart kecil untuk mini grafik
        $chart = LogSensor::query()
            ->select('suhu')
            ->latest('record_at')
            ->limit(20)
            ->get()
            ->reverse();

        $suhu = (float) $latest->suhu;
        $kelembapan = (float) $latest->kelembapan;

        return [

            Stat::make('Suhu Ruangan', $suhu . '°C')
                ->description($suhu > 30 ? 'Panas' : 'Normal')
                ->chart($chart->pluck('suhu'))
                ->color($suhu > 30 ? 'danger' : 'success'),

            Stat::make('Kelembapan', $kelembapan . '%')
                ->description('Kondisi udara lab')
                ->color('info'),
        ];
    }

    protected function getColumns(): int
    {
        return 2;
    }
}
