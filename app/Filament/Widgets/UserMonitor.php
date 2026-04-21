<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\DeviceState;
use Filament\Notifications\Notification;
use PhpMqtt\Client\Facades\MQTT;

class UserMonitor extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $device = DeviceState::where('device', 'esp32_smartlab_1')->first();

        if (!$device) {
            return [
                Stat::make('Status', 'OFFLINE')
                    ->description('Device belum terhubung')
                    ->color('danger'),
            ];
        }

        $user = $device->user ?? 'none';
        $isUsed = $user !== 'none';
        $isLocked = (bool) $device->locked;

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

    // ===== TOGGLE LOCK MQTT =====
    public function toggleLock()
    {
        $device = DeviceState::where('device', 'esp32_smartlab_1')->first();

        if (!$device) {
            Notification::make()
                ->title('Device tidak ditemukan')
                ->danger()
                ->send();

            return;
        }

        $newState = ! (bool) $device->locked;

        // publish ke MQTT
        MQTT::publish('lab1/control/lock', json_encode([
            'locked' => $newState
        ]));

        // notif UI
        Notification::make()
            ->title($newState ? 'System LOCKED' : 'System UNLOCKED')
            ->body('Perintah berhasil dikirim ke device')
            ->success()
            ->send();
    }
}
