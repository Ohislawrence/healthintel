<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Jobs\SendAppointmentReminder;
use Illuminate\Console\Command;

class SendReminderNotifications extends Command
{
    protected $signature = 'notifications:send-reminders';
    protected $description = 'Send push notifications for upcoming appointments and health tracker reminders';

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

        // ── Health Tracker Reminders ───────────────────────
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

            // Immunization: check for overdue vaccines (NPHCDA schedule)
            $immunization = $data['immunization'] ?? null;
            if ($immunization && !empty($immunization['children'])) {
                $vaccineSchedule = [
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
                    ['name' => 'Measles 1', 'ageWeeks' => 39],
                    ['name' => 'Yellow Fever', 'ageWeeks' => 39],
                ];
                foreach ($immunization['children'] as $child) {
                    $childRecords = $immunization['records'][$child['id']] ?? [];
                    $completed = collect($childRecords)->pluck('vaccineName')->toArray();
                    $dueNow = false;
                    foreach ($vaccineSchedule as $v) {
                        if (in_array($v['name'], $completed)) continue;
                        $dob = new \DateTime($child['dob']);
                        $dueDate = (clone $dob)->modify('+' . ($v['ageWeeks'] * 7) . ' days');
                        if (new \DateTime() >= $dueDate) { $dueNow = true; break; }
                    }
                    if ($dueNow) {
                        $reminders[] = "{$child['name']} may have overdue vaccines. Check the Immunization Tracker.";
                        break;
                    }
                }
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
                        ]
                    );
                    $trackerReminders++;
                }
            }
        }

        $this->info("Sent {$appointmentCount} appointment reminders and {$trackerReminders} health tracker reminders.");

        return Command::SUCCESS;
    }
}