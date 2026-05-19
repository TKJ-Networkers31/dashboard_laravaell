<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class LabDataUpdated implements ShouldBroadcast
{
    use SerializesModels;

    public array $data;
    public string $type; // 'sensor' | 'control' | 'access'

    public function __construct(array $data, string $type = 'sensor')
    {
        $this->data = $data;
        $this->type = $type;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('lab-channel');
    }

    public function broadcastAs(): string
    {
        return 'lab.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => $this->type,
            'data' => $this->data,
        ];
    }
}
