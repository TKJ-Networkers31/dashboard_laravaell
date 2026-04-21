<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use PhpMqtt\Client\Facades\MQTT;
use App\Services\mqtt\AutomationService;
use App\Actions\SaveSensorDataAction;
use Illuminate\Support\Facades\Cache;
use App\Events\LabDataUpdated;
use Illuminate\Support\Collection;

#[Signature('app:mqtt-subscribe')]
#[Description('Command description')]
class MqttSubscribe extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(SaveSensorDataAction $saveAction, AutomationService $automation)
    {
        $mqtt = MQTT::connection();
        $mqtt->subscribe('lab1/#', function (string $topic, string $message) use ($automation, $saveAction) {
            try {
                $data = json_decode($message, true);
                echo "Received message on {$topic}: {$message}\n";
                if ($topic === 'lab1/sensor') {
                    // ✅ simpan ke DB (punya kamu)
                    $saveAction->execute($data);
                    // 🔥 AMBIL CACHE LAMA
                    $cache = Cache::get('lab.dashboard', [
                        'latest' => null,
                        'chart' => collect(),
                        'device' => null,
                    ]);
                    $chart = collect($cache['chart']);
                    // 🔥 TAMBAH DATA BARU
                    $chart->push($data);
                    // 🔥 LIMIT 10 DATA
                    $chart = $chart->take(-10)->values();
                    // 🔥 SIMPAN KE CACHE
                    Cache::put('lab.dashboard', [
                        'latest' => $data,
                        'chart' => $chart,
                        'device' => $cache['device'],
                    ], 10);
                    // 🔥 BROADCAST KE FRONTEND
                    broadcast(new LabDataUpdated($data));
                } else {
                    // kontrol / automation
                    $automation->runAutomation($topic, $data);
                    // 🔥 update device di cache
                    $cache = Cache::get('lab.dashboard', [
                        'latest' => null,
                        'chart' => collect(),
                        'device' => null,
                    ]);
                    Cache::put('lab.dashboard', [
                        'latest' => $cache['latest'],
                        'chart' => $cache['chart'],
                        'device' => $data,
                    ], 10);
                    // 🔥 broadcast juga
                    broadcast(new LabDataUpdated($data));
                }
            } catch (\Exception $e) {
                echo "Error: " . $e->getMessage() . "\n";
            }
        }, 1);
        $mqtt->loop(true);
    }

}
