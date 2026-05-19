<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use PhpMqtt\Client\Facades\MQTT;
use App\Services\mqtt\AutomationService;
use App\Actions\SaveSensorDataAction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Events\LabDataUpdated;

#[Signature('app:mqtt-subscribe')]
#[Description('Subscribe ke broker MQTT dan proses data sensor serta kontrol Smart Lab')]
class MqttSubscribe extends Command
{
    /**
     * Durasi cache dalam detik.
     * Sensor publish tiap 10 detik → simpan cache 60 detik agar
     * widget yang polling 5 detik tidak pernah dapat data kosong.
     */
    private const CACHE_TTL     = 60;   // detik
    private const CHART_LIMIT   = 20;   // jumlah titik data chart

    public function handle(SaveSensorDataAction $saveAction, AutomationService $automation): void
    {
        $this->info('[MQTT] Connecting to broker...');

        $mqtt = MQTT::connection();

        $mqtt->subscribe('lab1/#', function (string $topic, string $message) use ($automation, $saveAction) {
            $this->line("[MQTT] [{$topic}] " . substr($message, 0, 120));

            try {
                $data = json_decode($message, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                $this->warn("[MQTT] JSON decode error on {$topic}: " . $e->getMessage());
                return;
            }

            if ($topic === 'lab1/sensor') {
                $this->handleSensor($data, $saveAction);
            } elseif ($topic === 'lab1/access') {
                $this->handleAccess($data, $automation);
            } elseif ($topic === 'lab1/timetolive') {
                $this->handleTTL($data);
            } else {
                // lab1/control/* → broadcast saja untuk update realtime dashboard
                $this->broadcastControlUpdate($data);
            }
        }, 1);

        $this->info('[MQTT] Subscribed to lab1/#. Listening...');
        $mqtt->loop(true);
    }

    // -----------------------------------------------------------------------
    // HANDLERS
    // -----------------------------------------------------------------------

    private function handleSensor(array $data, SaveSensorDataAction $saveAction): void
    {
        // 1. Simpan ke DB dan dapatkan record yang tersimpan
        $log = $saveAction->execute($data);

        if (! $log) {
            $this->warn('[MQTT] Failed to save sensor data');
            return;
        }

        // 2. Ambil cache lama
        $cache = $this->getCache();

        // 3. Tambah titik chart menggunakan data DB (konsisten dengan LabService)
        $chart = collect($cache['chart']);
        $chart->push($log->toArray());

        // 4. Batasi jumlah titik
        $chart = $chart->slice(-self::CHART_LIMIT)->values();

        // 5. Simpan kembali ke cache
        Cache::put('lab.dashboard', [
            'latest' => $log->toArray(),
            'chart'  => $chart->toArray(),
            'device' => $cache['device'],
        ], self::CACHE_TTL);

        // 6. Broadcast ke frontend
        broadcast(new LabDataUpdated($log->toArray(), 'sensor'))->toOthers();
    }

    private function handleAccess(array $data, AutomationService $automation): void
    {
        // AutomationService memproses validasi kartu dan kirim balasan MQTT
        $automation->runAutomation('lab1/access', $data);
    }

    private function handleTTL(array $data): void
    {
        // ESP32 mengirim heartbeat TTL - update device state di cache
        $cache = $this->getCache();

        Cache::put('lab.dashboard', [
            'latest' => $cache['latest'],
            'chart'  => $cache['chart'],
            'device' => array_merge($cache['device'] ?? [], [
                'device'    => $data['device'] ?? 'unknown',
                'last_seen' => now()->toIso8601String(),
            ]),
        ], self::CACHE_TTL);

        $this->line('[MQTT] TTL heartbeat from: ' . ($data['device'] ?? 'unknown'));
    }

    private function broadcastControlUpdate(array $data): void
    {
        // Kontrol (lamp, door, mode, lock) diterima dari ESP32 via lab1/sensor
        // atau sebagai konfirmasi. Broadcast ke dashboard.
        broadcast(new LabDataUpdated($data, 'control'))->toOthers();
    }

    // -----------------------------------------------------------------------
    // HELPERS
    // -----------------------------------------------------------------------

    private function getCache(): array
    {
        return Cache::get('lab.dashboard', [
            'latest' => null,
            'chart'  => [],
            'device' => null,
        ]);
    }
}
