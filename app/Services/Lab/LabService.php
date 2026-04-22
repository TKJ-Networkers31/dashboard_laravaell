<?php

namespace App\Services\Lab;

use App\Models\LogSensor;
use App\Models\DeviceState;

class LabService
{
    public function getAll(): array
    {
        $latest = LogSensor::latest('record_at')->first();
        $device = DeviceState::where('device', 'esp32_smartlab_1')->first();
        $chart = LogSensor::latest('record_at')->limit(20)->get()->reverse();

        return [
            'latest' => $latest ? $latest->toArray() : [],
            'device' => $device ? $device->toArray() : [],
            'chart'  => $chart,
        ];
    }
}
