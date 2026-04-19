<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use PhpMqtt\Client\Facades\MQTT;
use App\Models\DeviceState;
use App\Models\LogSensor;

#[Signature('app:mqtt-subscribe')]
#[Description('Command description')]
class MqttSubscribe extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $mqtt = MQTT::connection();
        // Subscribe to a topic with QoS level 1
        $mqtt->subscribe('lab1/#', function (string $topic, string $message) {
            try{
                if($topic === 'lab1/sensor'){
                    $data = json_decode($message, true);
                    echo "Received message on {$topic}: {$message}\n";
                    LogSensor::create([
                        'device'=>$data['device'],
                        'rssi'=>$data['rssi'],
                        'suhu'=>$data['temp'],
                        'kelembapan'=>$data['hum'],
                        'cahaya'=>$data['light'],
                        'jarak_objek'=>$data['distance'],
                        'sisa_memori'=>$data['freeMemory'],
                        'max_size_memori'=>$data['maxAlloc'],
                    ]);
                    DeviceState::where('device',$data['device'])->update([
                        'IP'=>$data['ip'],
                        'mode_auto'=>$data['modeAuto'],
                        'locked'=>$data['locked'],
                        'login'=>$data['login'],
                        'user'=>$data['user'],
                        'UID'=>$data['uid'],
                        'pintu'=>$data['door'],
                        'lampu1_2'=>$data['lamp1_2'],
                        'lampu3_4'=>$data['lamp3_4'],
                    ]);
                }
            }catch (\Exception $e){
                echo "Error: " . $e->getMessage() . "\n";
            }
            // Handle the received message

            // if($topic === 'lab1/access'){
            // }

        }, 1);
        // Start the event loop to process incoming messages
        $mqtt->loop(true);

    }
}
