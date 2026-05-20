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
    public array $pendingCommands = [];

    protected function getStats(): array
    {
        $this->checkCommandTimeouts();

        $data   = app(LabService::class)->getAll();
        $log    = $data['latest'] ?? [];
        $device = $data['device'] ?? [];

        $isOffline = empty($device);
        $isAuto    = (bool) ($device['mode_auto'] ?? false);
        $isLocked  = (bool) ($device['locked'] ?? false);
        $doorOpen  = (bool) ($device['pintu'] ?? false);

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
                    $isOffline ? 'gray'
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
                    'class'      => ($isOffline || isset($this->pendingCommands['mode_auto']))
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
                    $isOffline ? 'gray'
                        : (($log['cahaya'] ?? 0) > 0 ? 'warning' : 'gray')
                ),

            // ===== LAMPU 1_2 =====
            Stat::make(
                'Lampu 1 & 2',
                $this->getPendingLabel('lamp1_2',
                    $isOffline ? '-' : (($device['lampu1_2'] ?? false) ? 'Menyala' : 'Padam')
                )
            )
                ->description(
                    isset($this->pendingCommands['lamp1_2'])
                        ? '⏳ Menunggu konfirmasi ESP32...'
                        : ($isOffline ? 'Device offline'
                            : ($isAuto ? 'Mode otomatis aktif' : 'Klik untuk toggle'))
                )
                ->descriptionIcon(
                    ($device['lampu1_2'] ?? false) ? 'heroicon-m-sun' : 'heroicon-m-moon'
                )
                ->color(
                    isset($this->pendingCommands['lamp1_2'])
                        ? 'warning'
                        : ($isOffline ? 'gray'
                            : (($device['lampu1_2'] ?? false) ? 'warning' : 'gray'))
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
                'Lampu 3 & 4',
                $this->getPendingLabel('lamp3_4',
                    $isOffline ? '-' : (($device['lampu3_4'] ?? false) ? 'Menyala' : 'Padam')
                )
            )
                ->description(
                    isset($this->pendingCommands['lamp3_4'])
                        ? '⏳ Menunggu konfirmasi ESP32...'
                        : ($isOffline ? 'Device offline'
                            : ($isAuto ? 'Mode otomatis aktif' : 'Klik untuk toggle'))
                )
                ->descriptionIcon(
                    ($device['lampu3_4'] ?? false) ? 'heroicon-m-sun' : 'heroicon-m-moon'
                )
                ->color(
                    isset($this->pendingCommands['lamp3_4'])
                        ? 'warning'
                        : ($isOffline ? 'gray'
                            : (($device['lampu3_4'] ?? false) ? 'warning' : 'gray'))
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
            // Mode manual: 2 tombol terpisah (BUKA & TUTUP), bukan toggle
            // Mode otomatis: hanya tampil status, tidak bisa diklik
            Stat::make(
                'Akses Pintu',
                $this->getPendingLabel('door',
                    $isOffline ? '-' : ($doorOpen ? 'TERBUKA' : 'TERKUNCI')
                )
            )
                ->description(
                    isset($this->pendingCommands['door'])
                        ? '⏳ Menunggu konfirmasi ESP32...'
                        : ($isOffline ? 'Device offline'
                            : ($isAuto
                                ? 'Dikontrol otomatis'
                                : ($doorOpen
                                    ? 'Klik TUTUP untuk mengunci'
                                    : 'Klik BUKA untuk membuka')))
                )
                ->descriptionIcon(
                    $doorOpen ? 'heroicon-m-lock-open' : 'heroicon-m-lock-closed'
                )
                ->color(
                    isset($this->pendingCommands['door'])
                        ? 'warning'
                        : ($isOffline ? 'gray'
                            : ($doorOpen ? 'danger' : 'success'))
                )
                ->extraAttributes(
                    (! $isAuto && ! $isOffline && ! isset($this->pendingCommands['door']))
                        ? [
                            // Klik stat → open jika sedang tutup, tutup jika sedang buka
                            'class'      => 'cursor-pointer transition hover:scale-105 active:scale-95',
                            'wire:click' => $doorOpen ? 'closeDoor' : 'openDoor',
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
            $this->notifyDeviceNotFound();
            return;
        }

        $newState  = ! (bool) $device->mode_auto;
        $label     = $newState ? 'Mode OTOMATIS' : 'Mode MANUAL';
        $published = $this->publishMqtt('lab1/control/mode', ['mode_auto' => $newState]);

        if (! $published) {
            $this->notifyMqttFailed();
            return;
        }

        app(MqttCommandService::class)->storePending('mode_auto', $newState);
        $this->pendingCommands['mode_auto'] = [
            'label'   => $label,
            'sent_at' => now()->timestamp,
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
            $this->notifyDeviceNotFound();
            return;
        }

        $newState   = ! (bool) $device->$lamp;
        $topic      = $lamp === 'lampu1_2' ? 'lab1/control/lamp1_2' : 'lab1/control/lamp3_4';
        $commandKey = $lamp === 'lampu1_2' ? 'lamp1_2' : 'lamp3_4';
        $label      = $lamp === 'lampu1_2' ? 'Lampu 1 & 2' : 'Lampu 3 & 4';
        $action     = $newState ? 'Dinyalakan' : 'Dipadamkan';
        $payloadKey = $lamp === 'lampu1_2' ? 'lampu1_2' : 'lampu3_4';

        $published = $this->publishMqtt($topic, [$payloadKey => $newState]);
        if (! $published) {
            $this->notifyMqttFailed();
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

    /**
     * Mode manual: buka pintu secara eksplisit (door = true).
     * Setelah dibuka, harus diklik TUTUP untuk menutup — tidak auto-tutup.
     */
    public function openDoor(): void
    {
        $device = DeviceState::where('device', 'esp32_smartlab_1')->first();
        if (! $device) {
            $this->notifyDeviceNotFound();
            return;
        }

        if ((bool) $device->pintu) {
            // Sudah terbuka, tidak perlu kirim lagi
            Notification::make()
                ->title('Pintu sudah terbuka')
                ->body('Pintu sudah dalam kondisi terbuka.')
                ->info()
                ->send();
            return;
        }

        $published = $this->publishMqtt('lab1/control/door', ['door' => true]);
        if (! $published) {
            $this->notifyMqttFailed();
            return;
        }

        app(MqttCommandService::class)->storePending('door', true);
        $this->pendingCommands['door'] = [
            'label'   => 'Pintu Dibuka',
            'sent_at' => now()->timestamp,
        ];

        Notification::make()
            ->title('Perintah Terkirim: Buka Pintu')
            ->body('Menunggu konfirmasi dari ESP32... (maks 30 detik)')
            ->warning()
            ->send();
    }

    /**
     * Mode manual: tutup pintu secara eksplisit (door = false).
     * Pintu hanya bisa ditutup saat mode manual.
     */
    public function closeDoor(): void
    {
        $device = DeviceState::where('device', 'esp32_smartlab_1')->first();
        if (! $device) {
            $this->notifyDeviceNotFound();
            return;
        }

        if (! (bool) $device->pintu) {
            Notification::make()
                ->title('Pintu sudah terkunci')
                ->body('Pintu sudah dalam kondisi terkunci.')
                ->info()
                ->send();
            return;
        }

        $published = $this->publishMqtt('lab1/control/door', ['door' => false]);
        if (! $published) {
            $this->notifyMqttFailed();
            return;
        }

        app(MqttCommandService::class)->storePending('door', false);
        $this->pendingCommands['door'] = [
            'label'   => 'Pintu Ditutup',
            'sent_at' => now()->timestamp,
        ];

        Notification::make()
            ->title('Perintah Terkirim: Tutup Pintu')
            ->body('Menunggu konfirmasi dari ESP32... (maks 30 detik)')
            ->warning()
            ->send();
    }

    // -----------------------------------------------------------------------
    // POLLING: cek timeout & konfirmasi
    // -----------------------------------------------------------------------

    private function checkCommandTimeouts(): void
    {
        if (empty($this->pendingCommands)) return;

        $now          = now()->timestamp;
        $stillPending = array_keys(app(MqttCommandService::class)->getPending());

        foreach ($this->pendingCommands as $key => $cmd) {
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
            \Illuminate\Support\Facades\Log::error('[LabMonitor] MQTT publish failed: ' . $e->getMessage());
            return false;
        }
    }

    private function getPendingLabel(string $commandKey, string $defaultLabel): string
    {
        return isset($this->pendingCommands[$commandKey])
            ? '⏳ ' . $defaultLabel
            : $defaultLabel;
    }

    private function notifyDeviceNotFound(): void
    {
        Notification::make()
            ->title('Device Tidak Ditemukan')
            ->body('Tidak dapat menemukan device esp32_smartlab_1.')
            ->danger()
            ->send();
    }

    private function notifyMqttFailed(): void
    {
        Notification::make()
            ->title('Gagal Mengirim Perintah')
            ->body('Tidak dapat terhubung ke broker MQTT. Cek koneksi server.')
            ->danger()
            ->send();
    }
}