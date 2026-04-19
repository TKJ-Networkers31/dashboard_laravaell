<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use PhpMqtt\Client\Facades\MQTT;
use App\Services\mqtt\AutomationService;
use App\Actions\SaveSensorDataAction;

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
            try{
                if($topic === 'lab1/sensor'){
                    $data = json_decode($message, true);
                    echo "Received message on {$topic}: {$message}\n";
                    $saveAction->execute($data);
                }else{
                    echo "Received message on {$topic}: {$message}\n";
                    $data = json_decode($message, true);
                    $automation->runAutomation($topic, $data);
                }
            }catch (\Exception $e){
                echo "Error: " . $e->getMessage() . "\n";
            }

        }, 1);
        $mqtt->loop(true);

    }
}
