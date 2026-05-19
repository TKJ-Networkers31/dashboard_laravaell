<?php

namespace App\Services\mqtt;

use PhpMqtt\Client\Facades\MQTT;
use App\Models\CardAccess;
use App\Models\DeviceState;
use Illuminate\Support\Facades\Log;

class AutomationService
{
    /**
     * Tangani logika otomasi berdasarkan topik MQTT yang masuk.
     *
     * Topik yang ditangani:
     *  - lab1/access  → validasi kartu RFID, kirim balasan login/logout/denied
     */
    public function runAutomation(string $topic, array $data): void
    {
        try {
            if ($topic === 'lab1/access') {
                $this->handleAccess($data);
            }
        } catch (\Throwable $e) {
            Log::error('[AutomationService] Error on topic ' . $topic . ': ' . $e->getMessage());
        }
    }

    // -----------------------------------------------------------------------
    // PRIVATE
    // -----------------------------------------------------------------------

    private function handleAccess(array $data): void
    {
        $deviceId = $data['device'] ?? null;
        $uid      = $this->normalizeUid($data['uid'] ?? '');
        $status   = $data['status'] ?? 'login'; // ESP32 kirim 'login' atau 'logout'

        if (empty($deviceId) || empty($uid)) {
            Log::warning('[AutomationService] Access payload invalid', $data);
            return;
        }

        // Cek apakah device dalam status terkunci
        $deviceState = DeviceState::where('device', $deviceId)->first();
        if (! $deviceState) {
            Log::warning('[AutomationService] Device not found: ' . $deviceId);
            return;
        }

        if ($deviceState->locked) {
            // Sistem terkunci, tolak semua akses kartu
            MQTT::publish('lab1/control/login', json_encode([
                'statusAccess' => 'locked',
                'user'         => 'none',
                'uid'          => $uid,
            ]));
            return;
        }

        // Cari kartu di database (normalisasi UID)
        $card = CardAccess::findByUid($uid);

        if (! $card) {
            // Kartu tidak terdaftar
            Log::info('[AutomationService] UID not found: ' . $uid);
            MQTT::publish('lab1/control/login', json_encode([
                'statusAccess' => 'denied',
                'user'         => 'none',
                'uid'          => 'none',
            ]));
            return;
        }

        // Kartu terdaftar → kirim sukses
        // ESP32 akan toggle login/logout sendiri berdasarkan state internalnya.
        // Server hanya memvalidasi bahwa kartu dikenal.
        Log::info('[AutomationService] Access granted for: ' . $card->pengguna . ' UID: ' . $uid);

        MQTT::publish('lab1/control/login', json_encode([
            'statusAccess' => 'success',
            'user'         => $card->pengguna,
            'uid'          => $uid,
        ]));
    }

    /**
     * Normalisasi UID dari ESP32.
     * ESP32 mengirim format: " AB CD EF 01" (dengan spasi, uppercase)
     * Database menyimpan dalam format yang sama atau tanpa spasi.
     * Fungsi ini trim & uppercase agar konsisten.
     */
    private function normalizeUid(string $uid): string
    {
        return strtoupper(trim($uid));
    }
}
