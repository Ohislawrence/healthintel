<?php

namespace App\Http\Controllers\Api;

use App\Models\Appointment;
use App\Jobs\SendAppointmentReminder;
use App\Models\ProviderDirectoryEntry;
use App\Services\BookingService;
use Illuminate\Http\Request;

class AppointmentController extends BaseController
{
    public function __construct(
        private BookingService $bookingService,
    ) {}

    /**
     * Public cost of booking an appointment with a provider.
     */
    public function bookingCost()
    {
        return $this->success(['credit_cost' => $this->bookingService->bookingCreditCost()]);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $status = $request->query('status');

        $query = Appointment::where('user_id', $user->id)
            ->with('provider:id,name,type,specialty,city,state,phone')
            ->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc');

        if ($status && in_array($status, ['upcoming', 'pending', 'confirmed', 'declined', 'completed', 'cancelled'])) {
            $query->where('status', $status);
        }

        $appointments = $query->get();

        $active = $appointments->filter(fn ($a) => in_array($a->status, ['pending', 'confirmed', 'upcoming']))->values();
        $past = $appointments->filter(fn ($a) => in_array($a->status, ['completed', 'cancelled', 'declined']))->values();

        return $this->success([
            'upcoming' => $active,
            'past' => $past,
            'counts' => [
                'upcoming' => $active->count(),
                'pending' => Appointment::where('user_id', $user->id)->where('status', 'pending')->count(),
                'confirmed' => Appointment::where('user_id', $user->id)->where('status', 'confirmed')->count(),
                'completed' => Appointment::where('user_id', $user->id)->where('status', 'completed')->count(),
                'cancelled' => Appointment::where('user_id', $user->id)->where('status', 'cancelled')->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|date_format:H:i',
            'provider_id' => 'nullable|exists:provider_directory_entries,id',
            'patient_name' => 'nullable|string|max:255',
            'patient_phone' => 'nullable|string|max:30',
            'notes' => 'nullable|string|max:2000',
            'reminder_enabled' => 'nullable|boolean',
            'reminder_minutes_before' => 'nullable|integer|min:5|max:10080',
        ]);

        $needsReview = !empty($validated['provider_id']);

        $appointment = Appointment::create([
            'user_id' => $user->id,
            ...$validated,
            'status' => $needsReview ? 'pending' : 'upcoming',
            'patient_name' => $validated['patient_name'] ?? $user->name,
            'patient_phone' => $validated['patient_phone'] ?? $user->phone,
        ]);

        if ($needsReview) {
            try {
                $this->bookingService->charge($user, $appointment);
            } catch (\RuntimeException $e) {
                $appointment->delete();
                return $this->error('Insufficient credits to request this booking. Top up to continue.', 422);
            }

            $appointment = $appointment->fresh()->load('provider:id,name,type,specialty,city,state,phone');
            $this->bookingService->notifyProviderOfBooking($appointment);
        } else {
            $appointment->load('provider:id,name,type,specialty,city,state,phone');
            if ($appointment->reminder_enabled) {
                $this->scheduleReminder($appointment);
            }
        }

        return $this->success($appointment, $needsReview
            ? 'Booking request sent. The provider will confirm shortly.'
            : 'Appointment created', 201);
    }

    public function show(Request $request, $id)
    {
        $appointment = Appointment::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with('provider:id,name,type,specialty,city,state,phone')
            ->firstOrFail();

        return $this->success($appointment);
    }

    public function update(Request $request, $id)
    {
        $appointment = Appointment::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'appointment_date' => 'sometimes|date',
            'appointment_time' => 'sometimes|date_format:H:i',
            'provider_id' => 'nullable|exists:provider_directory_entries,id',
            'status' => 'nullable|in:upcoming,pending,confirmed,completed,cancelled',
            'notes' => 'nullable|string|max:2000',
            'reminder_enabled' => 'nullable|boolean',
            'reminder_minutes_before' => 'nullable|integer|min:5|max:10080',
        ]);

        // Users can only move to terminal states from their own side.
        if (isset($validated['status']) && !in_array($validated['status'], ['completed', 'cancelled', 'upcoming'])) {
            unset($validated['status']);
        }

        $appointment->update($validated);
        $appointment->load('provider:id,name,type,specialty,city,state,phone');

        if ($appointment->reminder_enabled && $appointment->status === 'upcoming') {
            $this->scheduleReminder($appointment);
        }

        return $this->success($appointment, 'Appointment updated');
    }

    public function cancel(Request $request, $id)
    {
        $appointment = Appointment::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (!in_array($appointment->status, ['pending', 'confirmed', 'upcoming'])) {
            return $this->error('This appointment can no longer be cancelled.', 422);
        }

        $appointment->update(['status' => 'cancelled']);

        // Refund credits if this was a charged provider booking.
        $this->bookingService->refund($appointment->fresh());

        return $this->success($appointment->load('provider:id,name,type,specialty,city,state,phone'), 'Appointment cancelled');
    }

    public function destroy(Request $request, $id)
    {
        $appointment = Appointment::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $appointment->delete();

        return $this->success(null, 'Appointment deleted');
    }

    /**
     * Dispatch the reminder job for an upcoming appointment.
     */
    private function scheduleReminder(Appointment $appointment): void
    {
        $appointmentDateTime = \Carbon\Carbon::parse(
            $appointment->appointment_date->format('Y-m-d') . ' ' . ($appointment->appointment_time ?? '00:00')
        );
        $reminderAt = $appointmentDateTime->copy()->subMinutes($appointment->reminder_minutes_before);

        SendAppointmentReminder::dispatch($appointment->id)
            ->delay($reminderAt->isFuture() ? $reminderAt : null);
    }
}