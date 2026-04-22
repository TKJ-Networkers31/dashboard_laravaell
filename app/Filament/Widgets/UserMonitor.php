<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\DeviceState;
use App\Services\Lab\LabService;
use Filament\Notifications\Notification;
use PhpMqtt\Client\Facades\MQTT;

class UserMonitor extends BaseWidget
{
    protected static ?int $sort = 1;

    // Tambahkan polling agar status Lock/User terupdate otomatis
    protected ?string $pollingInterval = '5s';

    protected function getStats(): array
    {
        // Mengambil data melalui service agar sinkron dengan widget lain
        $data = app(LabService::class)->getAll();
        $deviceData = $data['device'] ?? [];

        if (empty($deviceData)) {
            return [
                Stat::make('Status', 'OFFLINE')
                    ->description('Device belum terhubung')
                    ->color('danger'),
            ];
        }

        // Konversi ke object atau gunakan array sesuai data dari service
        $user = $deviceData['user'] ?? 'none';
        $isUsed = $user !== 'none';
        $isLocked = (bool) ($deviceData['locked'] ?? false);

        return [

            // ===== STATUS LAB =====
            Stat::make(
                'Status Laboratorium',
                $isUsed ? 'Sedang Digunakan' : 'Kosong'
            )
                ->description($isUsed ? "Oleh: {$user}" : "Siap digunakan")
                ->descriptionIcon($isUsed ? 'heroicon-m-user' : 'heroicon-m-check-circle')
                ->color($isUsed ? 'danger' : 'success')
                ->extraAttributes(['class' => 'cursor-default']),

            // ===== TOGGLE LOCK =====
            Stat::make(
                'System Security',
                $isLocked ? 'LOCKED' : 'UNLOCKED'
            )
                ->description($isLocked ? 'Klik untuk UNLOCK' : 'Klik untuk LOCK')
                ->descriptionIcon($isLocked ? 'heroicon-m-lock-closed' : 'heroicon-m-lock-open')
                ->color($isLocked ? 'danger' : 'success')
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:scale-[1.02] transition',
                    'wire:click' => 'toggleLock',
                ]),
        ];
    }

    protected function getColumns(): int
    {
        return 2;
    }

    public function toggleLock(): void
    {
        $device = DeviceState::where('device', 'esp32_smartlab_1')->first();

        if (!$device) {
            Notification::make()
                ->title('Device Tidak Ditemukan')
                ->body('Tidak dapat menemukan device esp32_smartlab_1.')
                ->danger()
                ->send();
            return;
        }

        $newState = !(bool) $device->locked;

        // Payload JSON tetap sesuai kode asli Anda
        MQTT::publish('lab1/control/lock', json_encode([
            'locked' => $newState
        ]));

        Notification::make()
            ->title('System ' . ($newState ? 'LOCKED' : 'UNLOCKED'))
            ->body('Perintah berhasil dikirim ke device.')
            ->success()
            ->send();
    }
}
