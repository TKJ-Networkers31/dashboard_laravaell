<?php

namespace App\Services\mqtt;

use Illuminate\Support\Facades\Cache;

/**
 * MqttCommandService
 *
 * Menyimpan perintah yang dikirim ke ESP32 sebagai "pending",
 * lalu memverifikasi apakah ESP32 benar-benar mengeksekusi perintah
 * berdasarkan data sensor yang dikirim balik.
 *
 * Flow:
 * 1. Widget klik toggle → publish MQTT → simpan pending command di cache
 * 2. ESP32 terima, eksekusi, lalu publish lab1/sensor dengan state terbaru
 * 3. MqttSubscribe terima sensor → panggil verifyPending()
 * 4. Jika state cocok → broadcast event "command_confirmed" ke frontend
 * 5. Widget polling 5 detik → langsung kelihatan state baru
 */
class MqttCommandService
{
    private const CACHE_KEY = 'mqtt.pending_commands';
    private const TTL       = 30; // detik, setelah ini dianggap timeout/gagal

    /**
     * Simpan perintah yang baru dikirim ke ESP32.
     *
     * @param string $commandType  Misal: 'mode_auto', 'lamp1_2', 'door', 'locked'
     * @param mixed  $expectedValue Nilai yang diharapkan setelah ESP32 eksekusi
     * @param string $deviceId     ID device target
     */
    public function storePending(string $commandType, mixed $expectedValue, string $deviceId = 'esp32_smartlab_1'): void
    {
        $pending = Cache::get(self::CACHE_KEY, []);

        $pending[$commandType] = [
            'expected'  => $expectedValue,
            'device'    => $deviceId,
            'sent_at'   => now()->timestamp,
            'confirmed' => false,
        ];

        Cache::put(self::CACHE_KEY, $pending, self::TTL);
    }

    /**
     * Verifikasi pending commands berdasarkan data sensor terbaru dari ESP32.
     * Dipanggil oleh MqttSubscribe setiap kali data sensor masuk.
     *
     * @param  array $sensorData Data yang diterima dari ESP32 (sudah di-parse)
     * @return array  [ ['command' => '...', 'confirmed' => true/false], ... ]
     */
    public function verifyPending(array $sensorData): array
    {
        $pending = Cache::get(self::CACHE_KEY, []);
        if (empty($pending)) {
            return [];
        }

        $results = [];
        $now     = now()->timestamp;

        // Mapping: command type → field di payload sensor dari ESP32
        $fieldMap = [
            'mode_auto' => 'modeAuto',
            'locked'    => 'locked',
            'lamp1_2'   => 'lamp1_2',
            'lamp3_4'   => 'lamp3_4',
            'door'      => 'door',
        ];

        foreach ($pending as $commandType => $cmd) {
            // Skip kalau sudah timeout
            if ($now - $cmd['sent_at'] > self::TTL) {
                $results[] = [
                    'command'   => $commandType,
                    'confirmed' => false,
                    'reason'    => 'timeout',
                ];
                unset($pending[$commandType]);
                continue;
            }

            $sensorField = $fieldMap[$commandType] ?? null;
            if (! $sensorField || ! isset($sensorData[$sensorField])) {
                continue;
            }

            $actualValue = $sensorData[$sensorField];
            $confirmed   = ($actualValue == $cmd['expected']);

            if ($confirmed) {
                $results[] = [
                    'command'   => $commandType,
                    'confirmed' => true,
                    'reason'    => 'confirmed_by_esp32',
                ];
                unset($pending[$commandType]);
            }
        }

        // Update cache
        if (empty($pending)) {
            Cache::forget(self::CACHE_KEY);
        } else {
            Cache::put(self::CACHE_KEY, $pending, self::TTL);
        }

        return $results;
    }

    /**
     * Cek apakah ada command yang sudah timeout (tidak dikonfirmasi ESP32).
     * Bisa dipanggil oleh widget saat polling untuk notifikasi gagal.
     */
    public function checkTimeouts(): array
    {
        $pending = Cache::get(self::CACHE_KEY, []);
        if (empty($pending)) {
            return [];
        }

        $now     = now()->timestamp;
        $expired = [];

        foreach ($pending as $commandType => $cmd) {
            if ($now - $cmd['sent_at'] > self::TTL && ! $cmd['confirmed']) {
                $expired[] = $commandType;
                unset($pending[$commandType]);
            }
        }

        if (! empty($expired)) {
            if (empty($pending)) {
                Cache::forget(self::CACHE_KEY);
            } else {
                Cache::put(self::CACHE_KEY, $pending, self::TTL);
            }
        }

        return $expired;
    }

    /**
     * Ambil semua pending commands (untuk ditampilkan di widget sebagai "menunggu konfirmasi").
     */
    public function getPending(): array
    {
        return Cache::get(self::CACHE_KEY, []);
    }

    /**
     * Clear semua pending (misalnya saat koneksi MQTT hilang).
     */
    public function clearAll(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}