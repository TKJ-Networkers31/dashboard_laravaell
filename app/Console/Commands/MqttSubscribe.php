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
        array              $data,
        SaveSensorDataAction $saveAction,
        MqttCommandService $commandService
    ): void {
        // 1. Simpan ke DB — SaveSensorDataAction juga update device_state
        $log = $saveAction->execute($data);

        if (! $log) {
            $this->warn('[MQTT] Failed to save sensor data');
            return;
        }

        // 2. Update last_seen karena sensor = device aktif
        $this->touchLastSeen($data['device'] ?? null);

        // 3. Verifikasi pending commands
        $confirmations = $commandService->verifyPending($data);
        foreach ($confirmations as $result) {
            broadcast(new LabDataUpdated([
                'command'  => $result['command'],
                'confirmed'=> $result['confirmed'],
                'reason'   => $result['reason'],
            ], $result['confirmed'] ? 'command_confirmed' : 'command_failed'));

            $this->line(sprintf(
                '[MQTT] Command %s: %s (%s)',
                $result['command'],
                $result['confirmed'] ? 'CONFIRMED' : 'FAILED',
                $result['reason']
            ));
        }

        // 4. Update cache
        $cache = $this->getCache();
        $chart = collect($cache['chart']);
        $chart->push($log->toArray());
        $chart = $chart->slice(-self::CHART_LIMIT)->values();

        Cache::put('lab.dashboard', [
            'latest' => $log->toArray(),
            'chart'  => $chart->toArray(),
            'device' => $cache['device'],
        ], self::CACHE_TTL);

        // 5. Broadcast
        broadcast(new LabDataUpdated($log->toArray(), 'sensor'))->toOthers();
    }

    private function handleAccess(array $data, AutomationService $automation): void
    {
        $automation->runAutomation('lab1/access', $data);
    }

    private function handleTTL(array $data): void
    {
        $deviceId = $data['device'] ?? null;

        // Update last_seen — ini tanda utama device masih hidup
        $this->touchLastSeen($deviceId);

        $cache = $this->getCache();

        Cache::put('lab.dashboard', [
            'latest' => $cache['latest'],
            'chart'  => $cache['chart'],
            'device' => array_merge($cache['device'] ?? [], [
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
     * Update kolom last_seen di DB setiap ada pesan dari device.
     * Ini yang jadi patokan online/offline di LabService.
     */
    private function touchLastSeen(?string $deviceId): void
    {
        if (! $deviceId) {
            return;
        }

        DeviceState::where('device', $deviceId)
            ->update(['last_seen' => now()]);
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