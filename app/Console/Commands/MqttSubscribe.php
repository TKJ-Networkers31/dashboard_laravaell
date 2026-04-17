<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use PhpMqtt\Client\Facades\MQTT;

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
        $mqtt->subscribe('some/topic', function (string $topic, string $message) {
            // Handle the received message
            echo "Received message on {$topic}: {$message}";
        }, 1);
        // Start the event loop to process incoming messages
        $mqtt->loop(true);

    }
}
