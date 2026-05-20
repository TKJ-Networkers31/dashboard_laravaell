<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use PhpMqtt\Client\Facades\MQTT;
use App\Services\mqtt\AutomationService;
use App\Services\mqtt\MqttCommandService;
use App\Actions\SaveSensorDataAction;
use App\Models\DeviceState;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Events\LabDataUpdated;

#[Signature('app:mqtt-subscribe')]
#[Description('Subscribe ke broker MQTT dan proses data sensor serta kontrol Smart Lab')]
class MqttSubscribe extends Command
{
    private const CACHE_TTL   = 60;
    private const CHART_LIMIT = 20;

    public function handle(
        SaveSensorDataAction $saveAction,
        AutomationService    $automation,
        MqttCommandService   $commandService
    ): void {
        $this->info('[MQTT] Connecting to broker...');

        $mqtt = MQTT::connection();

        $mqtt->subscribe('lab1/#', function (string $topic, string $message) use ($automation, $saveAction, $commandService) {
            $this->line("[MQTT] [{$topic}] " . substr($message, 0, 120));

            try {
                $data = json_decode($message, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                $this->warn("[MQTT] JSON decode error on {$topic}: " . $e->getMessage());
                return;
            }

            if ($topic === 'lab1/sensor') {
                $this->handleSensor($data, $saveAction, $commandService);
            } elseif ($topic === 'lab1/access') {
                $this->handleAccess($data, $automation);
            } elseif ($topic === 'lab1/timetolive') {
                $this->handleTTL($data);
            } else {
                $this->broadcastControlUpdate($data);
            }
        }, 1);

        $this->info('[MQTT] Subscribed to lab1/#. Listening...');
        $mqtt->loop(true);
    }

    // -----------------------------------------------------------------------
    // HANDLERS
    // -----------------------------------------------------------------------

    private function handleSensor(
        array                $data,
        SaveSensorDataAction $saveAction,
        MqttCommandService   $commandService
    ): void {
        // 1. Simpan ke DB — SaveSensorDataAction juga update device_state
        $log = $saveAction->execute($data);

        if (! $log) {
            $this->warn('[MQTT] Failed to save sensor data');
            return;
        }

        // 2. Update last_seen karena sensor = device aktif
        $this->touchLastSeen($data['device'] ?? null);

        // 3. Sinkronisasi login state dari payload ESP32 ke DB
        //    ESP32 selalu kirim state terkini: login, user, uid
        //    Ini fallback jika AutomationService update tidak ter-reflect
        $this->syncLoginStateFromSensor($data);

        // 4. Verifikasi pending commands
        $confirmations = $commandService->verifyPending($data);
        foreach ($confirmations as $result) {
            broadcast(new LabDataUpdated([
                'command'   => $result['command'],
                'confirmed' => $result['confirmed'],
                'reason'    => $result['reason'],
            ], $result['confirmed'] ? 'command_confirmed' : 'command_failed'));

            $this->line(sprintf(
                '[MQTT] Command %s: %s (%s)',
                $result['command'],
                $result['confirmed'] ? 'CONFIRMED' : 'FAILED',
                $result['reason']
            ));
        }

        // 5. Update cache — sertakan device state terbaru agar widget langsung update
        $cache      = $this->getCache();
        $chart      = collect($cache['chart']);
        $chart->push($log->toArray());
        $chart = $chart->slice(-self::CHART_LIMIT)->values();

        // Ambil device state fresh dari DB (sudah di-update oleh AutomationService)
        $deviceState = DeviceState::where('device', $data['device'] ?? '')->first();

        Cache::put('lab.dashboard', [
            'latest' => $log->toArray(),
            'chart'  => $chart->toArray(),
            'device' => $deviceState ? $deviceState->toArray() : $cache['device'],
        ], self::CACHE_TTL);

        // 6. Broadcast sensor update ke frontend (Livewire polling akan ambil dari cache)
        broadcast(new LabDataUpdated(array_merge(
            $log->toArray(),
            ['device_state' => $deviceState ? $deviceState->toArray() : []]
        ), 'sensor'))->toOthers();
    }

    private function handleAccess(array $data, AutomationService $automation): void
    {
        $automation->runAutomation('lab1/access', $data);

        // Setelah akses diproses, broadcast perubahan state ke frontend
        $deviceId    = $data['device'] ?? null;
        $deviceState = $deviceId
            ? DeviceState::where('device', $deviceId)->first()
            : null;

        if ($deviceState) {
            broadcast(new LabDataUpdated(
                $deviceState->toArray(),
                'control'
            ))->toOthers();

            // Invalidate cache agar LabService ambil fresh dari DB
            $this->invalidateDeviceCache($deviceId, $deviceState);
        }
    }

    private function handleTTL(array $data): void
    {
        $deviceId = $data['device'] ?? null;

        // Update last_seen — tanda utama device masih hidup
        $this->touchLastSeen($deviceId);

        $cache       = $this->getCache();
        $deviceState = $deviceId
            ? DeviceState::where('device', $deviceId)->first()
            : null;

        Cache::put('lab.dashboard', [
            'latest' => $cache['latest'],
            'chart'  => $cache['chart'],
            'device' => $deviceState ? $deviceState->toArray() : array_merge($cache['device'] ?? [], [
                'device'    => $deviceId ?? 'unknown',
                'last_seen' => now()->toIso8601String(),
            ]),
        ], self::CACHE_TTL);

        $this->line('[MQTT] TTL heartbeat from: ' . ($deviceId ?? 'unknown'));
    }

    private function broadcastControlUpdate(array $data): void
    {
        broadcast(new LabDataUpdated($data, 'control'))->toOthers();
    }

    // -----------------------------------------------------------------------
    // HELPERS
    // -----------------------------------------------------------------------

    /**
     * Sinkronisasi login state dari payload sensor ESP32.
     * ESP32 selalu kirim state aktualnya di setiap publish sensor.
     * Ini memastikan DB selalu sinkron dengan state di ESP32.
     */
    private function syncLoginStateFromSensor(array $data): void
    {
        $deviceId = $data['device'] ?? null;
        if (! $deviceId) return;

        // Hanya sync jika ESP32 kirim field login
        if (! isset($data['login'])) return;

        $login = (bool) $data['login'];
        $user  = $data['user'] ?? 'none';
        $uid   = $data['uid']  ?? 'none';

        // Hanya update login/user/uid, jangan overwrite field lain
        // karena SaveSensorDataAction sudah handle field sensor
        DeviceState::where('device', $deviceId)->update([
            'login' => $login,
            'user'  => $login ? $user : 'none',
            'UID'   => $login ? $uid  : 'none',
        ]);

        if ($login) {
            $this->line("[MQTT] Sync login state: {$user} ({$uid})");
        }
    }

    /**
     * Update last_seen di DB setiap ada pesan dari device.
     */
    private function touchLastSeen(?string $deviceId): void
    {
        if (! $deviceId) return;

        DeviceState::where('device', $deviceId)
            ->update(['last_seen' => now()]);
    }

    /**
     * Invalidate bagian device di cache agar LabService refresh dari DB.
     */
    private function invalidateDeviceCache(string $deviceId, DeviceState $deviceState): void
    {
        $cache = $this->getCache();
        Cache::put('lab.dashboard', [
            'latest' => $cache['latest'],
            'chart'  => $cache['chart'],
            'device' => $deviceState->toArray(),
        ], self::CACHE_TTL);
    }

    private function getCache(): array
    {
        return Cache::get('lab.dashboard', [
            'latest' => null,
            'chart'  => [],
            'device' => null,
        ]);
    }
}