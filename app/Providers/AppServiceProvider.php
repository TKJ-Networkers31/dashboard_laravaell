<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\DeviceState;
use App\Observers\DeviceStateObserver;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        DeviceState::observe(DeviceStateObserver::class);
    }
}
