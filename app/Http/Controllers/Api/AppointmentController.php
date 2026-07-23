<?php

namespace App\Http\Controllers\Api;

use App\Models\Appointment;
use Illuminate\Http\Request;

class AppointmentController extends BaseController
{
    public function index(Request $request)
    {
        $user = $request->user();
        $status = $request->query('status');

        $query = Appointment::where('user_id', $user->id)
            ->with('provider:id,name,type,specialty,city,state,phone')
            ->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc');

        if ($status && in_array($status, ['upcoming', 'completed', 'cancelled'])) {
            $query->where('status', $status);
        }

        $appointments = $query->get();

        $upcoming = $appointments->filter(fn($a) => $a->status === 'upcoming')->values();
        $past = $appointments->filter(fn($a) => $a->status !== 'upcoming')->values();

        return $this->success([
            'upcoming' => $upcoming,
            'past' => $past,
            'counts' => [
                'upcoming' => $upcoming->count(),
                'completed' => Appointment::where('user_id', $user->id)->where('status', 'completed')->count(),
                'cancelled' => Appointment::where('user_id', $user->id)->where('status', 'cancelled')->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|date_format:H:i',
            'provider_id' => 'nullable|exists:provider_directory_entries,id',
            'notes' => 'nullable|string|max:2000',
            'reminder_enabled' => 'nullable|boolean',
            'reminder_minutes_before' => 'nullable|integer|min:5|max:10080',
        ]);

        $appointment = Appointment::create([
            'user_id' => $request->user()->id,
            ...$validated,
        ]);

        $appointment->load('provider:id,name,type,specialty,city,state,phone');

        return $this->success($appointment, 'Appointment created', 201);
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
            'status' => 'nullable|in:upcoming,completed,cancelled',
            'notes' => 'nullable|string|max:2000',
            'reminder_enabled' => 'nullable|boolean',
            'reminder_minutes_before' => 'nullable|integer|min:5|max:10080',
        ]);

        $appointment->update($validated);
        $appointment->load('provider:id,name,type,specialty,city,state,phone');

        return $this->success($appointment, 'Appointment updated');
    }

    public function destroy(Request $request, $id)
    {
        $appointment = Appointment::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $appointment->delete();

        return $this->success(null, 'Appointment deleted');
    }
}