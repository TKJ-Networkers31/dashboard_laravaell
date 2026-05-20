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
            Log::info('[AutomationService] UID not found: ' . $uid);
            MQTT::publish('lab1/control/login', json_encode([
                'statusAccess' => 'denied',
                'user'         => 'none',
                'uid'          => 'none',
            ]));
            return;
        }

        // ---------------------------------------------------------------
        // Kartu terdaftar → tentukan apakah ini login atau logout
        // ---------------------------------------------------------------
        if ($status === 'logout') {
            // LOGOUT: reset state di DB
            $deviceState->update([
                'login' => false,
                'user'  => 'none',
                'UID'   => 'none',
            ]);

            Log::info('[AutomationService] LOGOUT: ' . $card->pengguna . ' UID: ' . $uid);

            MQTT::publish('lab1/control/login', json_encode([
                'statusAccess' => 'success',
                'user'         => $card->pengguna,
                'uid'          => $uid,
            ]));

        } else {
            // LOGIN: cek apakah sudah ada yang login
            if ($deviceState->login && $deviceState->UID !== $uid) {
                // Orang lain sedang login → denied
                Log::info('[AutomationService] Denied: another user already logged in. UID: ' . $uid);
                MQTT::publish('lab1/control/login', json_encode([
                    'statusAccess' => 'denied',
                    'user'         => 'none',
                    'uid'          => 'none',
                ]));
                return;
            }

            // Set state login di DB
            $deviceState->update([
                'login' => true,
                'user'  => $card->pengguna,
                'UID'   => $uid,
            ]);

            Log::info('[AutomationService] LOGIN: ' . $card->pengguna . ' UID: ' . $uid);

            MQTT::publish('lab1/control/login', json_encode([
                'statusAccess' => 'success',
                'user'         => $card->pengguna,
                'uid'          => $uid,
            ]));
        }
    }

    /**
     * Normalisasi UID: trim & uppercase, konsisten dengan ESP32 format "AB CD EF 01".
     */
    private function normalizeUid(string $uid): string
    {
        return strtoupper(trim($uid));
    }
}