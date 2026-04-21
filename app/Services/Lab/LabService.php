<?php
namespace App\Services\Lab;

use App\Models\LogSensor;
use App\Models\DeviceState;
use Illuminate\Support\Facades\Cache;

class LabService
{
    public function getAll()
    {
        return Cache::get('lab.dashboard', [
            'latest' => null,
            'chart' => collect(),
            'device' => null,
        ]);
    }
}
