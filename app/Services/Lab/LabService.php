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
     * Aturan:
     * - 'device' SELALU dari DB (fresh), bukan cache — supaya toggle langsung keliatan
     * - 'device' dikembalikan sebagai array KOSONG jika device offline
     *   (berdasarkan last_seen), sehingga semua widget otomatis masuk state offline
     * - 'latest' & 'chart' dari cache jika ada, fallback ke DB
     */
    public function getAll(): array
    {
        $cache  = Cache::get('lab.dashboard');
        $device = $this->getDeviceIfOnline();

        if ($cache && ! empty($cache['latest'])) {
            return [
                'latest' => $cache['latest'],
                'chart'  => collect($cache['chart'] ?? []),
                'device' => $device,
            ];
        }

        return $this->getFromDb($device);
    }

    /**
     * Ambil raw DeviceState dari DB (untuk keperluan toggle action,
     * tidak peduli online/offline — kita butuh current DB state).
     */
    public function getRawDevice(): ?DeviceState
    {
        return DeviceState::where('device', 'esp32_smartlab_1')->first();
    }

    // -----------------------------------------------------------------------
    // PRIVATE
    // -----------------------------------------------------------------------

    /**
     * Kembalikan device state sebagai array HANYA jika device online.
     * Jika offline atau belum pernah konek → return array kosong [].
     * Array kosong = semua widget tampilkan state offline.
     */
    private function getDeviceIfOnline(): array
    {
        $device = DeviceState::where('device', 'esp32_smartlab_1')->first();

        if (! $device || ! $device->isOnline()) {
            return [];
        }

        return $device->toArray();
    }

    private function getFromDb(array $device): array
    {
        $latest = LogSensor::latest('record_at')->first();
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
}