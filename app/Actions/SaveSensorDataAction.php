<?php
namespace App\Actions;

use App\Models\LogSensor;
use App\Models\DeviceState;

class SaveSensorDataAction
{
    public function execute(array $data)
    {
        // Logika simpan log
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

        // Logika update status terakhir perangkat
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
}
