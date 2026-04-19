<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use PhpMqtt\Client\Facades\MQTT;

class ControlPanel extends Widget
{
    protected string $view = 'filament.widgets.control-panel';

    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 2;
     public function turnOn()
    {
        $command = [
            'locked'=>true
        ];
        MQTT::publish('lab1/control/lock', json_encode($command));
    }
    public function turnOff()
    {
        $command = [
            'locked'=>false
        ];
        MQTT::publish('lab1/control/lock', json_encode($command));
    }
}
