<?php

namespace App\Services\Lab;

use App\Models\LogSensor;
use App\Models\DeviceState;
use Illuminate\Support\Facades\Cache;

class LabService
{
    /**
     * Ambil semua data lab: sensor terbaru, chart, status perangkat.
     *
     * Strategi:
     * - Cache di-update oleh MqttSubscribe setiap kali data masuk.
     * - Jika cache miss (MQTT belum jalan / TTL habis), fallback ke DB.
     * - Widget Filament polling tiap 5 detik → pastikan selalu dapat data.
     */
    public function getAll(): array
    {
        $cache = Cache::get('lab.dashboard');

        if ($cache && ! empty($cache['latest'])) {
            return [
                'latest' => $cache['latest'],
                'chart'  => collect($cache['chart'] ?? []),
                'device' => $cache['device'] ?? $this->getDeviceFromDb(),
            ];
        }

        // Fallback: baca langsung dari DB
        return $this->getFromDb();
    }

    // -----------------------------------------------------------------------
    // PRIVATE
    // -----------------------------------------------------------------------

    private function getFromDb(): array
    {
        $latest = LogSensor::latest('record_at')->first();
        $device = $this->getDeviceFromDb();
        $chart  = LogSensor::latest('record_at')
            ->limit(20)
            ->get()
            ->reverse()
            ->values();

        return [
            'latest' => $latest ? $latest->toArray() : [],
            'device' => $device,
            'chart'  => $chart,
        ];
    }

    private function getDeviceFromDb(): array
    {
        $device = DeviceState::where('device', 'esp32_smartlab_1')->first();
        return $device ? $device->toArray() : [];
    }
}
