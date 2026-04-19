<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DeviceState extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'device_state';

    // public $timestamps = false;
    protected $fillable = [
        'device',
        'IP',
        'mode_auto',
        'locked',
        'login',
        'user',
        'UID',
        'pintu',
        'lampu1_2',
        'lampu3_4'
    ];
    protected $casts = [
        'mode_auto' => 'boolean',
        'locked'    => 'boolean',
        'login'     => 'boolean',
        'pintu'     => 'boolean',
        'lampu1_2'  => 'boolean',
        'lampu3_4'  => 'boolean',
    ];
}
