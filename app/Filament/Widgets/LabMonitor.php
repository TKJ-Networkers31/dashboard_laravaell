<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Services\Lab\LabService;
use App\Models\DeviceState;
use Filament\Notifications\Notification;
use PhpMqtt\Client\Facades\MQTT;

class LabMonitor extends BaseWidget
{
    protected static ?int $sort = 2;

    // Menambahkan polling agar data update otomatis tanpa refresh
    protected ?string $pollingInterval = '5s';

    protected function getStats(): array
    {
        // Mengambil data dari service terpusat
        $data = app(LabService::class)->getAll();

        $log = $data['latest'] ?? [];
        $device = $data['device'] ?? [];

        $isOffline = empty($device);
        $isAuto = $device['mode_auto'] ?? false;

        return [

            // ===== SIGNAL =====
            Stat::make(
                'Kekuatan Sinyal',
                $isOffline ? 'Tidak ada data' : (($log['rssi'] ?? '-') . ' dBm')
            )
                ->description(
                    $isOffline ? 'Device offline' : 'IP: ' . ($device['IP'] ?? '-')
                )
                ->descriptionIcon('heroicon-m-wifi')
                ->color(
                    $isOffline
                        ? 'gray'
                        : (($log['rssi'] ?? -100) > -50 ? 'success' : 'warning')
                ),

            // ===== MODE =====
            Stat::make(
                'Mode Operasi',
                $isOffline ? 'OFFLINE' : ($isAuto ? 'OTOMATIS' : 'MANUAL')
            )
                ->description(
                    $isOffline ? 'Tidak terhubung' : 'Klik untuk ubah mode'
                )
                ->descriptionIcon('heroicon-m-cpu-chip')
                ->color(
                    $isOffline ? 'gray' : ($isAuto ? 'primary' : 'danger')
                )
                ->extraAttributes([
                    'class' => $isOffline
                        ? 'opacity-60 cursor-not-allowed'
                        : 'cursor-pointer transition hover:scale-105 active:scale-95',
                    'wire:click' => $isOffline ? null : "toggleAutoMode",
                ]),

            // ===== CAHAYA =====
            Stat::make(
                'Intensitas Cahaya',
                $isOffline ? '-' : (($log['cahaya'] ?? '-') . ' Lux')
            )
                ->description(
                    $isOffline
                        ? 'Device offline'
                        : (($log['cahaya'] ?? 0) > 0 ? 'Lampu Menyala' : 'Gelap')
                )
                ->descriptionIcon(
                    ($log['cahaya'] ?? 0) > 0 ? 'heroicon-m-sun' : 'heroicon-m-moon'
                )
                ->color(
                    $isOffline
                        ? 'gray'
                        : (($log['cahaya'] ?? 0) > 0 ? 'warning' : 'gray')
                ),

            // ===== LAMPU 1_2 =====
            Stat::make(
                'Lampu 1_2',
                $isOffline ? '-' : (($device['lampu1_2'] ?? false) ? 'Menyala' : 'Padam')
            )
                ->description(
                    $isOffline
                        ? 'Device offline'
                        : ($isAuto ? 'Mode otomatis aktif' : 'Klik untuk toggle')
                )
                ->descriptionIcon(
                    ($device['lampu1_2'] ?? false)
                        ? 'heroicon-m-sun'
                        : 'heroicon-m-moon'
                )
                ->color(
                    $isOffline
                        ? 'gray'
                        : (($device['lampu1_2'] ?? false) ? 'warning' : 'gray')
                )
                ->extraAttributes(
                    (!$isAuto && !$isOffline)
                        ? [
                            'class' => 'cursor-pointer transition hover:scale-105 active:scale-95',
                            'wire:click' => "toggleLamp('lampu1_2')",
                        ]
                        : ['class' => 'opacity-60 cursor-not-allowed']
                ),

            // ===== LAMPU 3_4 =====
            Stat::make(
                'Lampu 3_4',
                $isOffline ? '-' : (($device['lampu3_4'] ?? false) ? 'Menyala' : 'Padam')
            )
                ->description(
                    $isOffline
                        ? 'Device offline'
                        : ($isAuto ? 'Mode otomatis aktif' : 'Klik untuk toggle')
                )
                ->descriptionIcon(
                    ($device['lampu3_4'] ?? false)
                        ? 'heroicon-m-sun'
                        : 'heroicon-m-moon'
                )
                ->color(
                    $isOffline
                        ? 'gray'
                        : (($device['lampu3_4'] ?? false) ? 'warning' : 'gray')
                )
                ->extraAttributes(
                    (!$isAuto && !$isOffline)
                        ? [
                            'class' => 'cursor-pointer transition hover:scale-105 active:scale-95',
                            'wire:click' => "toggleLamp('lampu3_4')",
                        ]
                        : ['class' => 'opacity-60 cursor-not-allowed']
                ),

            // ===== PINTU =====
            Stat::make(
                'Akses Pintu',
                $isOffline ? '-' : (($device['pintu'] ?? false) ? 'TERBUKA' : 'TERKUNCI')
            )
                ->description(
                    $isOffline
                        ? 'Device offline'
                        : ($isAuto ? 'Mode otomatis aktif' : 'Klik untuk toggle')
                )
                ->descriptionIcon(
                    ($device['pintu'] ?? false)
                        ? 'heroicon-m-lock-open'
                        : 'heroicon-m-lock-closed'
                )
                ->color(
                    $isOffline
                        ? 'gray'
                        : (($device['pintu'] ?? false) ? 'danger' : 'success')
                )
                ->extraAttributes(
                    (!$isAuto && !$isOffline)
                        ? [
                            'class' => 'cursor-pointer transition hover:scale-105 active:scale-95',
                            'wire:click' => "toggleDoor",
                        ]
                        : ['class' => 'opacity-60 cursor-not-allowed']
                ),
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }

    public function toggleAutoMode(): void
    {
        $device = DeviceState::where('device', 'esp32_smartlab_1')->first();

        if (!$device) {
            Notification::make()
                ->title('Error')
                ->body('Device tidak ditemukan.')
                ->danger()
                ->send();
            return;
        }

        $newState = !$device->mode_auto;

        MQTT::publish('lab1/control/mode', json_encode([
            'mode_auto' => $newState
        ]));

        Notification::make()
            ->title('Mode ' . ($newState ? 'AUTO' : 'MANUAL') . ' Aktif')
            ->body('Mode operasi berhasil diubah ke ' . ($newState ? 'Otomatis' : 'Manual') . '.')
            ->success()
            ->send();
    }

    public function toggleLamp(string $lamp): void
    {
        $device = DeviceState::where('device', 'esp32_smartlab_1')->first();

        if (!$device) {
            Notification::make()
                ->title('Error')
                ->body('Device tidak ditemukan.')
                ->danger()
                ->send();
            return;
        }

        $newState = !$device->$lamp;

        $topic = $lamp === 'lampu1_2'
            ? 'lab1/control/lamp1_2'
            : 'lab1/control/lamp3_4';

        $label = $lamp === 'lampu1_2' ? 'Lampu 1 & 2' : 'Lampu 3 & 4';

        MQTT::publish($topic, json_encode([
            $lamp => $newState
        ]));

        Notification::make()
            ->title($label . ' ' . ($newState ? 'Dinyalakan' : 'Dipadamkan'))
            ->body($label . ' berhasil ' . ($newState ? 'dinyalakan' : 'dipadamkan') . '.')
            ->success()
            ->send();
    }

    public function toggleDoor(): void
    {
        $device = DeviceState::where('device', 'esp32_smartlab_1')->first();

        if (!$device) {
            Notification::make()
                ->title('Error')
                ->body('Device tidak ditemukan.')
                ->danger()
                ->send();
            return;
        }

        $newState = !$device->pintu;

        MQTT::publish('lab1/control/door', json_encode([
            'door' => $newState
        ]));

        Notification::make()
            ->title('Pintu ' . ($newState ? 'Dibuka' : 'Dikunci'))
            ->body('Akses pintu berhasil ' . ($newState ? 'dibuka' : 'dikunci') . '.')
            ->success()
            ->send();
    }
}
