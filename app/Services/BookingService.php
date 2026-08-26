<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class BookingService
{
    private CreditService $credits;

    public function __construct(CreditService $credits)
    {
        $this->credits = $credits;
    }

    /**
     * Credit cost for a provider-sourced booking (0 = free).
     */
    public function bookingCreditCost(): int
    {
        return (int) Setting::getValue('bookings.credit_cost', 1);
    }

    /**
     * Charge the user credits for a provider booking.
     * Returns the charged amount (0 if free).
     *
     * @throws \RuntimeException on insufficient credits.
     */
    public function charge(User $user, Appointment $appointment): int
    {
        $cost = $this->bookingCreditCost();

        if ($cost <= 0) {
            $appointment->update(['credits_charged' => 0]);
            return 0;
        }

        $this->credits->debit($user, $cost, 'appointment_booking', $appointment);

        $appointment->update(['credits_charged' => $cost]);

        return $cost;
    }

    /**
     * Refund credits if the booking was charged and not yet refunded.
     */
    public function refund(Appointment $appointment): void
    {
        if (($appointment->credits_charged ?? 0) <= 0 || $appointment->refunded_at) {
            return;
        }

        try {
            $this->credits->credit(
                $appointment->user,
                $appointment->credits_charged,
                'appointment_refund',
                $appointment,
            );

            $appointment->update(['refunded_at' => now()]);
        } catch (\Throwable $e) {
            Log::warning('Booking refund failed: ' . $e->getMessage());
        }
    }

    /**
     * Notify the patient in-app about a booking decision.
     */
    public function notifyPatient(Appointment $appointment, bool $confirmed, ?string $note): void
    {
        try {
            $title = $confirmed ? '✅ Appointment confirmed' : '❌ Appointment declined';
            $body = "{$appointment->title} on {$appointment->appointment_date->format('M j, Y')} at {$appointment->appointment_time}";
            if ($note) {
                $body .= " — {$note}";
            }

            \App\Models\UserNotification::create([
                'user_id' => $appointment->user_id,
                'type' => 'appointment',
                'title' => $title,
                'body' => $body,
                'data' => ['appointment_id' => $appointment->id],
                'action_url' => '/health-tools/appointments',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Booking decision notify failed: ' . $e->getMessage());
        }
    }

    /**
     * Email the provider (best-effort) about a new booking request.
     */
    public function notifyProviderOfBooking(Appointment $appointment): void
    {
        $provider = $appointment->provider;
        if (!$provider || empty($provider->email)) {
            return;
        }

        try {
            \Mail::raw(
                "New booking request for {$provider->name}.\n\n"
                . "Patient: {$appointment->patient_name}\n"
                . "Phone: {$appointment->patient_phone}\n"
                . "Service: {$appointment->title}\n"
                . "Date: {$appointment->appointment_date->format('M j, Y')} at {$appointment->appointment_time}\n"
                . "Notes: " . ($appointment->notes ?: 'None') . "\n\n"
                . "Sign in to your partner dashboard to confirm or decline.",
                function ($message) use ($provider) {
                    $message->to($provider->email)
                        ->subject('New booking request — please review');
                }
            );
        } catch (\Throwable $e) {
            Log::warning('Provider booking notification failed: ' . $e->getMessage());
        }
    }
}