<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\DeviceState;
use App\Services\Lab\LabService;
use App\Services\mqtt\MqttCommandService;
use Filament\Notifications\Notification;
use PhpMqtt\Client\Facades\MQTT;

class UserMonitor extends BaseWidget
{
    protected static ?int $sort = 1;
    protected ?string $pollingInterval = '5s';

    public array $pendingCommands = [];

    protected function getStats(): array
    {
        // Cek timeout/konfirmasi setiap polling
        $this->checkCommandTimeouts();

        $data       = app(LabService::class)->getAll();
        $deviceData = $data['device'] ?? [];

        if (empty($deviceData)) {
            return [
                Stat::make('Status', 'OFFLINE')
                    ->description('Device belum terhubung')
                    ->color('danger'),
            ];
        }

        $user     = $deviceData['user'] ?? 'none';
        $isUsed   = $user !== 'none';
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
                $this->getPendingLabel('locked', $isLocked ? 'LOCKED' : 'UNLOCKED')
            )
                ->description(
                    isset($this->pendingCommands['locked'])
                        ? '⏳ Menunggu konfirmasi ESP32...'
                        : ($isLocked ? 'Klik untuk UNLOCK' : 'Klik untuk LOCK')
                )
                ->descriptionIcon($isLocked ? 'heroicon-m-lock-closed' : 'heroicon-m-lock-open')
                ->color(
                    isset($this->pendingCommands['locked'])
                        ? 'warning'
                        : ($isLocked ? 'danger' : 'success')
                )
                ->extraAttributes([
                    'class' => isset($this->pendingCommands['locked'])
                        ? 'opacity-60 cursor-not-allowed'
                        : 'cursor-pointer hover:scale-[1.02] transition',
                    'wire:click' => isset($this->pendingCommands['locked'])
                        ? null
                        : 'toggleLock',
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

        if (! $device) {
            Notification::make()
                ->title('Device Tidak Ditemukan')
                ->body('Tidak dapat menemukan device esp32_smartlab_1.')
                ->danger()
                ->send();
            return;
        }

        $newState = ! (bool) $device->locked;
        $label    = $newState ? 'LOCKED' : 'UNLOCKED';

        $published = $this->publishMqtt('lab1/control/locked', ['locked' => $newState]);

        if (! $published) {
            Notification::make()
                ->title('Gagal Mengirim Perintah')
                ->body('Tidak dapat terhubung ke broker MQTT. Cek koneksi server.')
                ->danger()
                ->send();
            return;
        }

        // Simpan sebagai pending
        app(MqttCommandService::class)->storePending('locked', $newState);
        $this->pendingCommands['locked'] = [
            'label'   => "System {$label}",
            'sent_at' => now()->timestamp,
        ];

        Notification::make()
            ->title("Perintah Terkirim: System {$label}")
            ->body('Menunggu konfirmasi dari ESP32... (maks 30 detik)')
            ->warning()
            ->send();
    }

    // -----------------------------------------------------------------------
    // POLLING: cek timeout & konfirmasi
    // -----------------------------------------------------------------------

    private function checkCommandTimeouts(): void
    {
        if (empty($this->pendingCommands)) {
            return;
        }

        $now          = now()->timestamp;
        $stillPending = array_keys(app(MqttCommandService::class)->getPending());

        foreach ($this->pendingCommands as $key => $cmd) {
            // Timeout
            if ($now - $cmd['sent_at'] > 30) {
                Notification::make()
                    ->title('Perintah Gagal / Timeout')
                    ->body("ESP32 tidak merespons '{$cmd['label']}' dalam 30 detik. Periksa koneksi MQTT.")
                    ->danger()
                    ->persistent()
                    ->send();
                unset($this->pendingCommands[$key]);
                continue;
            }

            // Dikonfirmasi (sudah dihapus dari pending cache oleh MqttSubscribe)
            if (! in_array($key, $stillPending, true)) {
                Notification::make()
                    ->title('✅ Berhasil Dikonfirmasi')
                    ->body("ESP32 berhasil mengeksekusi: {$cmd['label']}")
                    ->success()
                    ->send();
                unset($this->pendingCommands[$key]);
            }
        }
    }

    // -----------------------------------------------------------------------
    // HELPERS
    // -----------------------------------------------------------------------

    private function publishMqtt(string $topic, array $payload): bool
    {
        try {
            MQTT::publish($topic, json_encode($payload));
            return true;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[UserMonitor] MQTT publish failed: ' . $e->getMessage());
            return false;
        }
    }

    private function getPendingLabel(string $commandKey, string $defaultLabel): string
    {
        if (isset($this->pendingCommands[$commandKey])) {
            return '⏳ ' . $defaultLabel;
        }
        return $defaultLabel;
    }
}