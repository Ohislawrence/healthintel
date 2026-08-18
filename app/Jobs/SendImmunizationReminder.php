<?php

namespace App\Jobs;

use App\Services\WebPushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendImmunizationReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $userId,
        private string $childName,
        private array $dueVaccines,
    ) {}

    public function handle(WebPushService $webPush): void
    {
        $list = implode(', ', $this->dueVaccines);

        $webPush->sendToUser(
            $this->userId,
            '💉 Vaccination Reminder',
            "{$this->childName} may be due for: {$list}. Review the Immunization Tracker.",
            [
                'url' => '/health-tools/immunization',
                'tag' => 'immunization-' . $this->childName,
                'requireInteraction' => true,
            ]
        );
    }
}