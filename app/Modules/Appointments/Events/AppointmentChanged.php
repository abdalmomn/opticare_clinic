<?php

namespace App\Modules\Appointments\Events;

use App\Modules\Appointments\Models\Appointment;
use App\Modules\Authentication\Models\Staff;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppointmentChanged implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Appointment $appointment,
        public string $action,
        protected Staff $actor
    ) {}

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('appointments'),
            new PrivateChannel('appointments.' . $this->appointment->id),
        ];

        if ($this->appointment->doctor_id) {
            $channels[] = new PrivateChannel(
                'staff.' . $this->appointment->doctor_id . '.appointments'
            );
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'appointment.changed';
    }

    public function broadcastWith(): array
    {
        $this->appointment->loadMissing(['patient', 'doctor']);

        return [
            'action' => $this->action,
            'appointment_id' => $this->appointment->id,
            'status' => $this->appointment->status,
            'doctor_id' => $this->appointment->doctor_id,
            'queue_number' => $this->appointment->queue_number,
            'appointment' => $this->appointment->toArray(),
            'changed_by' => [
                'id' => $this->actor->id,
                'name' => $this->actor->name,
            ],
            'occurred_at' => now()->toISOString(),
        ];
    }
}
