<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Services\WebPushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAppointmentReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $appointmentId,
    ) {}

    public function handle(WebPushService $webPush): void
    {
        $appointment = Appointment::with('user')->find($this->appointmentId);

        if (! $appointment) return;

        // Only send if still upcoming
        if ($appointment->status !== 'upcoming') return;

        // Don't resend if already sent
        if ($appointment->reminder_sent_at) return;

        $timePrefix = $appointment->appointment_time
            ? " at {$appointment->appointment_time}"
            : '';

        $title = "⏰ Upcoming Appointment";
        $body = "{$appointment->title} is scheduled for " .
            $appointment->appointment_date->format('M j, Y') .
            "{$timePrefix}.";

        if ($appointment->notes) {
            $body .= " — {$appointment->notes}";
        }

        $webPush->sendToUser(
            $appointment->user_id,
            $title,
            $body,
            [
                'url' => '/health-tools/appointments',
                'tag' => "appointment-{$appointment->id}",
                'requireInteraction' => true,
                'in_app' => true,
                'in_app_type' => 'appointment',
            ]
        );

        $appointment->update(['reminder_sent_at' => now()]);
    }
}