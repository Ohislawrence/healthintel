<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Jobs\SendAppointmentReminder;
use App\Jobs\SendImmunizationReminder;
use App\Jobs\SendMedicationReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendReminderNotifications extends Command
{
    protected $signature = 'notifications:send-reminders';
    protected $description = 'Send push notifications for upcoming appointments and health tracker reminders';

    private const VACCINE_SCHEDULE = [
        ['name' => 'BCG', 'ageWeeks' => 0],
        ['name' => 'OPV 0', 'ageWeeks' => 0],
        ['name' => 'HepB Birth', 'ageWeeks' => 0],
        ['name' => 'OPV 1', 'ageWeeks' => 6],
        ['name' => 'Pentavalent 1', 'ageWeeks' => 6],
        ['name' => 'PCV 1', 'ageWeeks' => 6],
        ['name' => 'Rota 1', 'ageWeeks' => 6],
        ['name' => 'IPV 1', 'ageWeeks' => 6],
        ['name' => 'OPV 2', 'ageWeeks' => 10],
        ['name' => 'Pentavalent 2', 'ageWeeks' => 10],
        ['name' => 'PCV 2', 'ageWeeks' => 10],
        ['name' => 'Rota 2', 'ageWeeks' => 10],
        ['name' => 'OPV 3', 'ageWeeks' => 14],
        ['name' => 'Pentavalent 3', 'ageWeeks' => 14],
        ['name' => 'PCV 3', 'ageWeeks' => 14],
        ['name' => 'IPV 2', 'ageWeeks' => 14],
        ['name' => 'Vitamin A 1', 'ageWeeks' => 26],
        ['name' => 'Measles 1', 'ageWeeks' => 39],
        ['name' => 'Yellow Fever', 'ageWeeks' => 39],
        ['name' => 'Vitamin A 2', 'ageWeeks' => 52],
        ['name' => 'Measles 2', 'ageWeeks' => 65],
    ];

    public function handle(): int
    {
        $now = now();

        // ── Appointment Reminders ──────────────────────────
        $upcomingAppointments = Appointment::with('user')
            ->where('status', 'upcoming')
            ->where('reminder_enabled', true)
            ->whereNull('reminder_sent_at')
            ->get();

        $appointmentCount = 0;

        foreach ($upcomingAppointments as $appointment) {
            $appointmentDateTime = \Carbon\Carbon::parse(
                $appointment->appointment_date->format('Y-m-d') . ' ' . ($appointment->appointment_time ?? '00:00')
            );

            $reminderTime = $appointmentDateTime->copy()->subMinutes($appointment->reminder_minutes_before);

            // Only send if current time is within the reminder window
            if ($now->between($reminderTime, $appointmentDateTime)) {
                SendAppointmentReminder::dispatch($appointment->id);
                $appointmentCount++;
                $this->line("Dispatched reminder for appointment #{$appointment->id}: {$appointment->title}");
            }
        }

        // ── Health Tracker Reminders (today's snapshots) ────
        // Check users who logged BP/water/food today vs their goals
        $trackerReminders = 0;
        $snapshots = \App\Models\UserTrackerSnapshot::with('user')
            ->where('date', $now->toDateString())
            ->get();

        foreach ($snapshots as $snapshot) {
            $data = $snapshot->data ?? [];
            $reminders = [];

            // Blood pressure: remind if only 1 reading and it's before 6pm
            $bpReadings = $data['blood_pressure'] ?? [];
            if (count($bpReadings) === 1 && $now->hour >= 16 && $now->hour < 20) {
                $lastReading = $bpReadings[0];
                $sys = $lastReading['systolic'] ?? 0;
                if ($sys >= 140) {
                    $reminders[] = "Your last BP reading was elevated ({$lastReading['systolic']}/{$lastReading['diastolic']}). Consider a re-check.";
                } else {
                    $reminders[] = "Remember to log your evening blood pressure reading.";
                }
            }

            // Water intake: remind if below 2L by 4pm
            $waterIntake = $data['water_intake'] ?? [];
            $totalWater = collect($waterIntake)->sum('ml');
            if ($totalWater > 0 && $totalWater < 2000 && $now->hour >= 16 && $now->hour < 20) {
                $remaining = 2000 - $totalWater;
                $reminders[] = "You've logged {$totalWater}ml of water today. Try to reach at least 2000ml — {$remaining}ml to go!";
            }

            // Food diary: remind if no dinner logged by 7pm
            $foodEntries = $data['food_symptom'] ?? [];
            $hasDinner = collect($foodEntries)->contains(fn($e) => ($e['meal_type'] ?? '') === 'dinner');
            if (count($foodEntries) > 0 && !$hasDinner && $now->hour >= 19 && $now->hour < 22) {
                $reminders[] = "Don't forget to log your dinner in the Food & Symptom Diary.";
            }

            if (!empty($reminders)) {
                $webPush = app(\App\Services\WebPushService::class);
                foreach ($reminders as $msg) {
                    $webPush->sendToUser(
                        $snapshot->user_id,
                        '📋 Health Update',
                        $msg,
                        [
                            'url' => '/health-tools',
                            'tag' => 'health-tracker-reminder',
                            'requireInteraction' => false,
                            'in_app' => true,
                            'in_app_type' => 'health_update',
                        ]
                    );
                    $trackerReminders++;
                }
            }
        }

        // ── Immunization Reminders (latest snapshot per user) ──
        // Immunization data is persistent state, not daily logs — so read
        // each user's most recent snapshot, not only today's.
        $immunizationReminders = 0;
        $latestSnapshots = \App\Models\UserTrackerSnapshot::whereNotNull('data')
            ->orderBy('date', 'desc')
            ->get()
            ->unique('user_id');

        foreach ($latestSnapshots as $snapshot) {
            $immunization = $snapshot->data['immunization'] ?? null;
            if (!$immunization || empty($immunization['children'])) {
                continue;
            }

            foreach ($immunization['children'] as $child) {
                $childId = $child['id'] ?? null;
                $childName = $child['name'] ?? 'Your child';
                $dob = $child['dob'] ?? null;

                if (!$childId || !$dob) {
                    continue;
                }

                $childRecords = $immunization['records'][$childId] ?? [];
                $completed = collect($childRecords)->pluck('vaccineName')->toArray();

                $dueVaccines = [];
                foreach (self::VACCINE_SCHEDULE as $v) {
                    if (in_array($v['name'], $completed, true)) {
                        continue;
                    }
                    $dueDate = (clone new \DateTime($dob))->modify('+' . ($v['ageWeeks'] * 7) . ' days');
                    if (new \DateTime() >= $dueDate) {
                        $dueVaccines[] = $v['name'];
                    }
                }

                if (empty($dueVaccines)) {
                    continue;
                }

                // Dedupe: only nudge once per child per day.
                $cacheKey = "immunization-reminder:{$snapshot->user_id}:{$childId}";
                if (Cache::has($cacheKey)) {
                    continue;
                }

                Cache::put($cacheKey, true, now()->addDay());
                SendImmunizationReminder::dispatch($snapshot->user_id, $childName, $dueVaccines);
                $immunizationReminders++;
                $this->line("Dispatched immunization reminder for user #{$snapshot->user_id}: {$childName}");
            }
        }

        // ── Medication Reminders ─────────────────────────────
        $medicationReminders = 0;
        foreach ($snapshots as $snapshot) {
            $medications = $snapshot->data['medication'] ?? [];
            foreach ($medications as $med) {
                if (empty($med['reminder_enabled']) || empty($med['time'])) {
                    continue;
                }

                if ($now->format('H:i') !== substr($med['time'], 0, 5)) {
                    continue;
                }

                // Dedupe: one nudge per medication per day.
                $cacheKey = "medication-reminder:{$snapshot->user_id}:{$med['id']}";
                if (Cache::has($cacheKey)) {
                    continue;
                }

                Cache::put($cacheKey, true, now()->addDay());
                SendMedicationReminder::dispatch(
                    $snapshot->user_id,
                    $med['name'] ?? 'Medication',
                    $med['dosage'] ?? '',
                    $med['time']
                );
                $medicationReminders++;
                $this->line("Dispatched medication reminder for user #{$snapshot->user_id}: {$med['name']}");
            }
        }

        $this->info("Sent {$appointmentCount} appointment reminders, {$trackerReminders} health tracker reminders, {$immunizationReminders} immunization reminders, and {$medicationReminders} medication reminders.");

        return Command::SUCCESS;
    }
}