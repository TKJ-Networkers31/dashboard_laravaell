<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class DeviceState extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table   = 'device_state';

    // Anggap device offline jika tidak ada TTL/sensor dalam 90 detik
    // ESP32 kirim TTL tiap 60 detik, jadi 90 detik = toleransi 1.5x interval
    public const OFFLINE_THRESHOLD_SECONDS = 90;

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
        'lampu3_4',
        'last_seen',
    ];

    protected $casts = [
        'mode_auto' => 'boolean',
        'locked'    => 'boolean',
        'login'     => 'boolean',
        'pintu'     => 'boolean',
        'lampu1_2'  => 'boolean',
        'lampu3_4'  => 'boolean',
        'last_seen' => 'datetime',
    ];

    /**
     * Cek apakah device sedang online berdasarkan last_seen.
     * Online = last_seen tidak null DAN dalam 90 detik terakhir.
     */
    public function isOnline(): bool
    {
        if ($this->last_seen === null) {
            return false;
        }

        return $this->last_seen->diffInSeconds(now()) <= self::OFFLINE_THRESHOLD_SECONDS;
    }

    /**
     * Berapa detik sejak terakhir terlihat. Null jika belum pernah konek.
     */
    public function secondsSinceLastSeen(): ?int
    {
        if ($this->last_seen === null) {
            return null;
        }

        return (int) $this->last_seen->diffInSeconds(now());
    }
}