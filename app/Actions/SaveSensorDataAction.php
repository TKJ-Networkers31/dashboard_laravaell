<?php

namespace App\Actions;

use App\Models\LogSensor;
use App\Models\DeviceState;
use Illuminate\Support\Facades\Log;

class SaveSensorDataAction
{
    /**
     * Simpan data sensor dari ESP32 ke database.
     * ESP32 mengirim: device, rssi, temp, hum, light, distance, freeMemory, maxAlloc,
     *                 ip, modeAuto, locked, login, user, uid, door, lamp1_2, lamp3_4
     *
     * @return LogSensor|null
     */
    public function execute(array $data): ?LogSensor
    {
        try {
            // Validasi field wajib
            if (empty($data['device'])) {
                Log::warning('[SaveSensorData] Payload missing device field', $data);
                return null;
            }

            // Simpan log sensor
            $log = LogSensor::create([
                'device'         => $data['device'],
                'rssi'           => $data['rssi']        ?? '0',
                'suhu'           => $data['temp']        ?? 0,
                'kelembapan'     => $data['hum']         ?? 0,
                'cahaya'         => $data['light']       ?? 0,
                'jarak_objek'    => $data['distance']    ?? 0,
                'sisa_memori'    => $data['freeMemory']  ?? 0,
                'max_size_memori'=> $data['maxAlloc']    ?? 0,
            ]);

            // Update status perangkat
            DeviceState::where('device', $data['device'])->update([
                'IP'       => $data['ip']      ?? 'unknown',
                'mode_auto'=> $data['modeAuto'] ?? false,
                'locked'   => $data['locked']  ?? false,
                'login'    => $data['login']   ?? false,
                'user'     => $data['user']    ?? 'none',
                'UID'      => $data['uid']     ?? 'none',
                'pintu'    => $data['door']    ?? false,
                'lampu1_2' => $data['lamp1_2'] ?? false,
                'lampu3_4' => $data['lamp3_4'] ?? false,
            ]);

            return $log;
        } catch (\Throwable $e) {
            Log::error('[SaveSensorData] Error: ' . $e->getMessage(), [
                'data'  => $data,
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }
}
