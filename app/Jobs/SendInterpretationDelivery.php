<?php

namespace App\Jobs;

use App\Models\DeliveryAttempt;
use App\Models\PartnerInterpretation;
use App\Services\TermiiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendInterpretationDelivery implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60; // seconds between retries

    public function __construct(
        public int $interpretationId,
        public string $deliveryMethod,
        public string $recipient,
    ) {}

    public function handle(): void
    {
        $interpretation = PartnerInterpretation::find($this->interpretationId);
        if (!$interpretation) return;

        $attempt = DeliveryAttempt::where('interpretation_id', $this->interpretationId)
            ->latest('attempt_number')
            ->first();

        $attemptNumber = ($attempt ? $attempt->attempt_number : 0) + 1;

        try {
            $result = match ($this->deliveryMethod) {
                'email' => $this->deliverEmail($interpretation),
                'whatsapp' => $this->deliverWhatsApp($interpretation),
                'sms' => $this->deliverSms($interpretation),
                default => ['success' => false, 'error' => 'Unknown method'],
            };

            if ($result['success']) {
                DeliveryAttempt::create([
                    'interpretation_id' => $this->interpretationId,
                    'delivery_method' => $this->deliveryMethod,
                    'recipient' => $this->recipient,
                    'message_id' => $result['message_id'] ?? null,
                    'status' => 'sent',
                    'provider_response' => $result['response'] ?? null,
                    'attempt_number' => $attemptNumber,
                ]);

                $interpretation->update(['delivery_status' => 'sent']);
            } else {
                $this->handleFailure($interpretation, $attemptNumber, $result['error'] ?? 'Unknown error');
            }
        } catch (\Throwable $e) {
            $this->handleFailure($interpretation, $attemptNumber, $e->getMessage());
        }
    }

    private function handleFailure(PartnerInterpretation $interpretation, int $attemptNumber, string $error): void
    {
        $nextRetry = $this->calculateNextRetry($attemptNumber);

        DeliveryAttempt::create([
            'interpretation_id' => $this->interpretationId,
            'delivery_method' => $this->deliveryMethod,
            'recipient' => $this->recipient,
            'status' => 'failed',
            'error_message' => $error,
            'attempt_number' => $attemptNumber,
            'next_retry_at' => $nextRetry,
        ]);

        $interpretation->update(['delivery_status' => 'failed']);
    }

    private function calculateNextRetry(int $attempt): ?\Carbon\Carbon
    {
        // Exponential backoff: 1min, 5min, 15min
        if ($attempt >= 3) return null; // no more retries

        $minutes = match ($attempt) {
            1 => 1,
            2 => 5,
            default => 15,
        };

        return now()->addMinutes($minutes);
    }

    private function deliverEmail(PartnerInterpretation $i): array
    {
        $pdf = app(\App\Services\ReportRenderer::class)->renderSingle($i);

        Mail::send([], [], function ($message) use ($i, $pdf) {
            $message->to($this->recipient)
                ->subject('Your Lab Result Interpretation: ' . $i->test_name)
                ->html('<p>Your lab result interpretation is attached.</p>')
                ->attachData($pdf, 'interpretation.pdf', ['mime' => 'application/pdf']);
        });

        return ['success' => true, 'message_id' => 'email_' . time()];
    }

    private function deliverWhatsApp(PartnerInterpretation $i): array
    {
        $text = "*{$i->test_name} Result*\n\n"
            . "Value: {$i->value} {$i->unit}\n\n"
            . ($i->interpretation_text ? wordwrap($i->interpretation_text, 60) . "\n\n" : '')
            . "_This is not a medical diagnosis._";

        $termii = app(TermiiService::class);
        return $termii->sendWhatsApp($this->recipient, $text);
    }

    private function deliverSms(PartnerInterpretation $i): array
    {
        $text = "{$i->test_name}: {$i->value} {$i->unit}. "
            . ($i->interpretation_text
                ? substr(strip_tags($i->interpretation_text), 0, 300)
                : 'Result ready.');

        $termii = app(TermiiService::class);
        return $termii->sendSms($this->recipient, $text);
    }

    /**
     * Mark permanently failed after all retries exhausted.
     */
    public function failed(\Throwable $e): void
    {
        $interpretation = PartnerInterpretation::find($this->interpretationId);
        if ($interpretation) {
            $interpretation->update(['delivery_status' => 'failed_permanent']);

            DeliveryAttempt::create([
                'interpretation_id' => $this->interpretationId,
                'delivery_method' => $this->deliveryMethod,
                'recipient' => $this->recipient,
                'status' => 'failed',
                'error_message' => 'Permanent failure after 3 retries: ' . $e->getMessage(),
                'attempt_number' => 3,
                'next_retry_at' => null,
            ]);
        }
    }
}