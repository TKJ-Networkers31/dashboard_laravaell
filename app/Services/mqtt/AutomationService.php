<?php

namespace App\Services\mqtt;

use PhpMqtt\Client\Facades\MQTT;
use App\Models\LogSensor;
use App\Models\DeviceState;
use App\Models\CardAccess;


class AutomationService
{
    public function runAutomation($topic, array $datas)
    {
        $status = DeviceState::where('device', $datas['device'])->first();
        if (!$status) return;
        if($topic === 'lab1/access' && $status['locked'] === false){
            // $data = LogSensor::where('device',$datas['device'])->first();
            $data = CardAccess::where('UID',$datas['uid'])->first();
            if($data){

                MQTT::publish('lab1/control/login', json_encode([
                    "statusAccess"=> "success",
                    "user"=> $data->pengguna,
                    'uid' => $datas['uid']
                ]));
            }else {
                // Opsional: Kirim balasan jika akses ditolak
                MQTT::publish('lab1/control/login', json_encode([
                    "statusAccess" => "denied",
                    "user"         => "none",
                    'uid'          => 'none'
                ]));
            }
        }



    }
}
