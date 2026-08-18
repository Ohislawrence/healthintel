<?php

namespace App\Jobs;

use App\Services\WebPushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendMedicationReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $userId,
        private string $name,
        private string $dosage,
        private string $time,
    ) {}

    public function handle(WebPushService $webPush): void
    {
        $dosageLabel = $this->dosage !== '' ? " ({$this->dosage})" : '';

        $webPush->sendToUser(
            $this->userId,
            '💊 Medication Reminder',
            "Time to take {$this->name}{$dosageLabel} — scheduled for {$this->time}.",
            [
                'url' => '/health-tools/medication',
                'tag' => 'medication-' . $this->name,
                'requireInteraction' => true,
            ]
        );
    }
}