<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogSensor extends Model
{
    public $timestamps = false;
    protected $table = 'log_sensor';
    protected $fillable = [
        'device',
        'rssi',
        'suhu',
        'kelembapan',
        'cahaya',
        'jarak_objek',
        'sisa_memori',
        'max_size_memori',
    ] ;
}
