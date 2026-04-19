<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DeviceState;

class deviceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $devices = [
            [
                'device'=>'esp32_smartlab_1',
                'IP'=>'none',
                'mode_auto'=>true,
                'locked'=>false,
                'login'=>false,
                'user'=>'none',
                'UID'=>'none',
                'pintu'=>false,
                'lampu1_2'=>false,
                'lampu3_4'=>false
            ]
        ];
        foreach($devices as $data){
            DeviceState::updateOrCreate(
                ['device'=>$data['device']],
                [
                    'IP'=>$data['IP'],
                    'mode_auto'=>$data['mode_auto'],
                    'locked'=>$data['locked'],
                    'login'=>$data['login'],
                    'user'=>$data['user'],
                    'UID'=>$data['UID'],
                    'pintu'=>$data['pintu'],
                    'lampu1_2'=>$data['lampu1_2'],
                    'lampu3_4'=>$data['lampu3_4']
                ]
            );
        }
    }
}
