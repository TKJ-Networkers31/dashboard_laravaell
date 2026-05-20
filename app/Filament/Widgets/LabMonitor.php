<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Services\Lab\LabService;
use App\Services\mqtt\MqttCommandService;
use App\Models\DeviceState;
use Filament\Notifications\Notification;
use PhpMqtt\Client\Facades\MQTT;

class LabMonitor extends BaseWidget
{
    protected static ?int $sort = 2;
    protected ?string $pollingInterval = '5s';

    // Track commands yang sedang menunggu konfirmasi dari ESP32
    // Key: command type, Value: ['label' => '...', 'sent_at' => timestamp]
    public array $pendingCommands = [];

    protected function getStats(): array
    {
        // Cek timeout setiap polling
        $this->checkCommandTimeouts();

        $data    = app(LabService::class)->getAll();
        $log     = $data['latest'] ?? [];
        $device  = $data['device'] ?? [];

        $isOffline = empty($device);
        $isAuto    = $device['mode_auto'] ?? false;

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
                $this->getPendingLabel('mode_auto',
                    $isOffline ? 'OFFLINE' : ($isAuto ? 'OTOMATIS' : 'MANUAL')
                )
            )
                ->description(
                    isset($this->pendingCommands['mode_auto'])
                        ? '⏳ Menunggu konfirmasi ESP32...'
                        : ($isOffline ? 'Tidak terhubung' : 'Klik untuk ubah mode')
                )
                ->descriptionIcon('heroicon-m-cpu-chip')
                ->color(
                    isset($this->pendingCommands['mode_auto'])
                        ? 'warning'
                        : ($isOffline ? 'gray' : ($isAuto ? 'primary' : 'danger'))
                )
                ->extraAttributes([
                    'class' => ($isOffline || isset($this->pendingCommands['mode_auto']))
                        ? 'opacity-60 cursor-not-allowed'
                        : 'cursor-pointer transition hover:scale-105 active:scale-95',
                    'wire:click' => ($isOffline || isset($this->pendingCommands['mode_auto']))
                        ? null
                        : 'toggleAutoMode',
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
                $this->getPendingLabel('lamp1_2',
                    $isOffline ? '-' : (($device['lampu1_2'] ?? false) ? 'Menyala' : 'Padam')
                )
            )
                ->description(
                    isset($this->pendingCommands['lamp1_2'])
                        ? '⏳ Menunggu konfirmasi ESP32...'
                        : ($isOffline
                            ? 'Device offline'
                            : ($isAuto ? 'Mode otomatis aktif' : 'Klik untuk toggle'))
                )
                ->descriptionIcon(
                    ($device['lampu1_2'] ?? false) ? 'heroicon-m-sun' : 'heroicon-m-moon'
                )
                ->color(
                    isset($this->pendingCommands['lamp1_2'])
                        ? 'warning'
                        : ($isOffline ? 'gray' : (($device['lampu1_2'] ?? false) ? 'warning' : 'gray'))
                )
                ->extraAttributes(
                    (! $isAuto && ! $isOffline && ! isset($this->pendingCommands['lamp1_2']))
                        ? [
                            'class'      => 'cursor-pointer transition hover:scale-105 active:scale-95',
                            'wire:click' => "toggleLamp('lampu1_2')",
                        ]
                        : ['class' => 'opacity-60 cursor-not-allowed']
                ),

            // ===== LAMPU 3_4 =====
            Stat::make(
                'Lampu 3_4',
                $this->getPendingLabel('lamp3_4',
                    $isOffline ? '-' : (($device['lampu3_4'] ?? false) ? 'Menyala' : 'Padam')
                )
            )
                ->description(
                    isset($this->pendingCommands['lamp3_4'])
                        ? '⏳ Menunggu konfirmasi ESP32...'
                        : ($isOffline
                            ? 'Device offline'
                            : ($isAuto ? 'Mode otomatis aktif' : 'Klik untuk toggle'))
                )
                ->descriptionIcon(
                    ($device['lampu3_4'] ?? false) ? 'heroicon-m-sun' : 'heroicon-m-moon'
                )
                ->color(
                    isset($this->pendingCommands['lamp3_4'])
                        ? 'warning'
                        : ($isOffline ? 'gray' : (($device['lampu3_4'] ?? false) ? 'warning' : 'gray'))
                )
                ->extraAttributes(
                    (! $isAuto && ! $isOffline && ! isset($this->pendingCommands['lamp3_4']))
                        ? [
                            'class'      => 'cursor-pointer transition hover:scale-105 active:scale-95',
                            'wire:click' => "toggleLamp('lampu3_4')",
                        ]
                        : ['class' => 'opacity-60 cursor-not-allowed']
                ),

            // ===== PINTU =====
            Stat::make(
                'Akses Pintu',
                $this->getPendingLabel('door',
                    $isOffline ? '-' : (($device['pintu'] ?? false) ? 'TERBUKA' : 'TERKUNCI')
                )
            )
                ->description(
                    isset($this->pendingCommands['door'])
                        ? '⏳ Menunggu konfirmasi ESP32...'
                        : ($isOffline
                            ? 'Device offline'
                            : ($isAuto ? 'Mode otomatis aktif' : 'Klik untuk toggle'))
                )
                ->descriptionIcon(
                    ($device['pintu'] ?? false) ? 'heroicon-m-lock-open' : 'heroicon-m-lock-closed'
                )
                ->color(
                    isset($this->pendingCommands['door'])
                        ? 'warning'
                        : ($isOffline ? 'gray' : (($device['pintu'] ?? false) ? 'danger' : 'success'))
                )
                ->extraAttributes(
                    (! $isAuto && ! $isOffline && ! isset($this->pendingCommands['door']))
                        ? [
                            'class'      => 'cursor-pointer transition hover:scale-105 active:scale-95',
                            'wire:click' => 'toggleDoor',
                        ]
                        : ['class' => 'opacity-60 cursor-not-allowed']
                ),
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }

    // -----------------------------------------------------------------------
    // ACTIONS
    // -----------------------------------------------------------------------

    public function toggleAutoMode(): void
    {
        $device = DeviceState::where('device', 'esp32_smartlab_1')->first();

        if (! $device) {
            Notification::make()
                ->title('Device Tidak Ditemukan')
                ->body('Device esp32_smartlab_1 tidak ada di database.')
                ->danger()
                ->send();
            return;
        }

        $newState   = ! $device->mode_auto;
        $label      = $newState ? 'Mode OTOMATIS' : 'Mode MANUAL';
        $published  = $this->publishMqtt('lab1/control/mode', ['mode_auto' => $newState]);

        if (! $published) {
            Notification::make()
                ->title('Gagal Mengirim Perintah')
                ->body('Tidak dapat terhubung ke broker MQTT. Cek koneksi server.')
                ->danger()
                ->send();
            return;
        }

        // Simpan sebagai pending, tunggu konfirmasi dari ESP32
        app(MqttCommandService::class)->storePending('mode_auto', $newState);
        $this->pendingCommands['mode_auto'] = [
            'label'    => $label,
            'sent_at'  => now()->timestamp,
        ];

        Notification::make()
            ->title('Perintah Terkirim: ' . $label)
            ->body('Menunggu konfirmasi dari ESP32... (maks 30 detik)')
            ->warning()
            ->send();
    }

    public function toggleLamp(string $lamp): void
    {
        $device = DeviceState::where('device', 'esp32_smartlab_1')->first();

        if (! $device) {
            Notification::make()
                ->title('Device Tidak Ditemukan')
                ->danger()
                ->send();
            return;
        }

        $newState = ! $device->$lamp;

        $topic = $lamp === 'lampu1_2'
            ? 'lab1/control/lamp1_2'
            : 'lab1/control/lamp3_4';

        $commandKey = $lamp === 'lampu1_2' ? 'lamp1_2' : 'lamp3_4';
        $label      = $lamp === 'lampu1_2' ? 'Lampu 1 & 2' : 'Lampu 3 & 4';
        $action     = $newState ? 'Dinyalakan' : 'Dipadamkan';

        // Payload key harus cocok dengan yang dicek di ESP32
        $payloadKey = $lamp === 'lampu1_2' ? 'lampu1_2' : 'lampu3_4';
        $published  = $this->publishMqtt($topic, [$payloadKey => $newState]);

        if (! $published) {
            Notification::make()
                ->title('Gagal Mengirim Perintah')
                ->body('Tidak dapat terhubung ke broker MQTT.')
                ->danger()
                ->send();
            return;
        }

        app(MqttCommandService::class)->storePending($commandKey, $newState);
        $this->pendingCommands[$commandKey] = [
            'label'   => "{$label} {$action}",
            'sent_at' => now()->timestamp,
        ];

        Notification::make()
            ->title("Perintah Terkirim: {$label} {$action}")
            ->body('Menunggu konfirmasi dari ESP32... (maks 30 detik)')
            ->warning()
            ->send();
    }

    public function toggleDoor(): void
    {
        $device = DeviceState::where('device', 'esp32_smartlab_1')->first();

        if (! $device) {
            Notification::make()
                ->title('Device Tidak Ditemukan')
                ->danger()
                ->send();
            return;
        }

        $newState  = ! $device->pintu;
        $action    = $newState ? 'Dibuka' : 'Dikunci';
        $published = $this->publishMqtt('lab1/control/door', ['door' => $newState]);

        if (! $published) {
            Notification::make()
                ->title('Gagal Mengirim Perintah')
                ->body('Tidak dapat terhubung ke broker MQTT.')
                ->danger()
                ->send();
            return;
        }

        app(MqttCommandService::class)->storePending('door', $newState);
        $this->pendingCommands['door'] = [
            'label'   => "Pintu {$action}",
            'sent_at' => now()->timestamp,
        ];

        Notification::make()
            ->title("Perintah Terkirim: Pintu {$action}")
            ->body('Menunggu konfirmasi dari ESP32... (maks 30 detik)')
            ->warning()
            ->send();
    }

    // -----------------------------------------------------------------------
    // POLLING: cek timeout saat widget di-render ulang
    // -----------------------------------------------------------------------

    private function checkCommandTimeouts(): void
    {
        if (empty($this->pendingCommands)) {
            return;
        }

        $now = now()->timestamp;

        foreach ($this->pendingCommands as $key => $cmd) {
            // Timeout 30 detik
            if ($now - $cmd['sent_at'] > 30) {
                Notification::make()
                    ->title('Perintah Gagal / Timeout')
                    ->body("ESP32 tidak merespons perintah '{$cmd['label']}' dalam 30 detik. Periksa koneksi MQTT atau status device.")
                    ->danger()
                    ->persistent()
                    ->send();

                unset($this->pendingCommands[$key]);
            }
        }

        // Cek apakah ada konfirmasi masuk dari MqttCommandService
        // (state di cache sudah dihapus oleh MqttSubscribe saat confirmed)
        $stillPending = array_keys(app(MqttCommandService::class)->getPending());

        foreach ($this->pendingCommands as $key => $cmd) {
            if (! in_array($key, $stillPending, true)) {
                // Command sudah dikonfirmasi oleh ESP32 (dihapus dari pending cache)
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

    /**
     * Publish MQTT dengan error handling.
     * Return true jika berhasil terkirim, false jika gagal.
     */
    private function publishMqtt(string $topic, array $payload): bool
    {
        try {
            MQTT::publish($topic, json_encode($payload));
            return true;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[LabMonitor] MQTT publish failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Tampilkan label dengan indikator pending jika command sedang menunggu.
     */
    private function getPendingLabel(string $commandKey, string $defaultLabel): string
    {
        if (isset($this->pendingCommands[$commandKey])) {
            return '⏳ ' . $defaultLabel;
        }
        return $defaultLabel;
    }
}