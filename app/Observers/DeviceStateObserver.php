<?php

namespace App\Observers;

use App\Models\DeviceState;
use Illuminate\Support\Facades\Log;

class DeviceStateObserver
{
    /**
     * Dipanggil otomatis saat device baru pertama kali insert ke DB
     */
    public function created(DeviceState $device): void
    {
        Log::info('[DeviceState] Device baru terdaftar otomatis', [
            'device' => $device->device,
            'IP'     => $device->IP,
        ]);
    }

    public function updated(DeviceState $device): void
    {
        // opsional: log setiap update
    }
}
