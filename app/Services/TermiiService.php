<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * SMS and WhatsApp delivery service via Termii API.
 * Termii is the leading SMS/WhatsApp gateway in Nigeria.
 */
class TermiiService
{
    private string $apiKey;
    private string $baseUrl;
    private string $from;

    public function __construct()
    {
        $this->apiKey = config('services.termii.api_key', $_ENV['TERMII_API_KEY'] ?? '');
        $this->baseUrl = config('services.termii.base_url', 'https://api.ng.termii.com/api');
        $this->from = config('services.termii.sender_id', 'LabDoc');
    }

    /**
     * Send an SMS via Termii.
     */
    public function sendSms(string $to, string $message): array
    {
        if (empty($this->apiKey)) {
            return ['success' => false, 'error' => 'Termii API key not configured.'];
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/sms/send', [
                'api_key' => $this->apiKey,
                'to' => $this->cleanPhone($to),
                'from' => $this->from,
                'sms' => $message,
                'type' => 'plain',
                'channel' => 'generic',
            ]);

            $body = $response->json();

            return [
                'success' => $response->successful(),
                'message_id' => $body['message_id'] ?? null,
                'response' => $body,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send a WhatsApp message via Termii.
     */
    public function sendWhatsApp(string $to, string $message): array
    {
        if (empty($this->apiKey)) {
            return ['success' => false, 'error' => 'Termii API key not configured.'];
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/sms/send', [
                'api_key' => $this->apiKey,
                'to' => $this->cleanPhone($to),
                'from' => $this->from,
                'sms' => $message,
                'type' => 'plain',
                'channel' => 'whatsapp',
            ]);

            $body = $response->json();

            return [
                'success' => $response->successful(),
                'message_id' => $body['message_id'] ?? null,
                'response' => $body,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Clean a Nigerian phone number to international format.
     */
    private function cleanPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Strip leading 234 or +234
        if (str_starts_with($phone, '234')) {
            $phone = substr($phone, 3);
        }

        // Ensure it starts with 0 and is 11 digits
        if (strlen($phone) === 10) {
            $phone = '0' . $phone;
        }

        if (!str_starts_with($phone, '0')) {
            $phone = '0' . $phone;
        }

        return $phone;
    }
}