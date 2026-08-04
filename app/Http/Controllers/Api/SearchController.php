<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProviderDirectoryEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Comprehensive in-app page index.
     */
    private function appPages(): array
    {
        return [
            // Core
            ['k' => 'dashboard', 't' => 'Dashboard', 's' => 'Your health overview', 'u' => '/dashboard', 'i' => '⌂'],
            ['k' => 'lab results', 't' => 'Lab Results', 's' => 'Upload or enter lab values', 'u' => '/lab-results', 'i' => '⚛'],
            ['k' => 'lab test', 't' => 'Lab Results', 's' => 'Upload or enter lab values', 'u' => '/lab-results', 'i' => '⚛'],
            ['k' => 'lab panel', 't' => 'Lab Results', 's' => 'Upload or enter lab values', 'u' => '/lab-results', 'i' => '⚛'],
            ['k' => 'upload lab', 't' => 'Lab Results', 's' => 'Upload or enter lab values', 'u' => '/lab-results', 'i' => '⚛'],
            ['k' => 'symptoms', 't' => 'Symptom Checker', 's' => 'AI-powered health guidance', 'u' => '/symptom-checker', 'i' => '♡'],
            ['k' => 'symptom checker', 't' => 'Symptom Checker', 's' => 'AI-powered health guidance', 'u' => '/symptom-checker', 'i' => '♡'],
            ['k' => 'directory', 't' => 'Provider Directory', 's' => 'Find doctors & labs near you', 'u' => '/directory', 'i' => '⚕'],
            ['k' => 'provider', 't' => 'Provider Directory', 's' => 'Find doctors & labs near you', 'u' => '/directory', 'i' => '⚕'],
            ['k' => 'doctors', 't' => 'Provider Directory', 's' => 'Find doctors near you', 'u' => '/directory', 'i' => '⚕'],
            ['k' => 'hospital', 't' => 'Provider Directory', 's' => 'Find hospitals near you', 'u' => '/directory', 'i' => '⚕'],
            ['k' => 'insurance', 't' => 'Insurance Comparison', 's' => 'Compare HMO & insurance plans', 'u' => '/insurance', 'i' => '🛡️'],
            ['k' => 'hmo', 't' => 'Insurance Comparison', 's' => 'Compare HMO & insurance plans', 'u' => '/insurance', 'i' => '🛡️'],

            // Credits & Referral
            ['k' => 'credits', 't' => 'My Credits', 's' => 'View credit balance and history', 'u' => '/credits', 'i' => '◆'],
            ['k' => 'buy credits', 't' => 'Buy Credits', 's' => 'Top up your credits', 'u' => '/credits/buy', 'i' => '◆'],
            ['k' => 'top up', 't' => 'Buy Credits', 's' => 'Top up your credits', 'u' => '/credits/buy', 'i' => '◆'],
            ['k' => 'payment', 't' => 'Buy Credits', 's' => 'Top up your credits', 'u' => '/credits/buy', 'i' => '◆'],
            ['k' => 'referral', 't' => 'Referral Program', 's' => 'Invite friends, earn credits', 'u' => '/referral', 'i' => '👥'],
            ['k' => 'refer', 't' => 'Referral Program', 's' => 'Invite friends, earn credits', 'u' => '/referral', 'i' => '👥'],
            ['k' => 'invite', 't' => 'Referral Program', 's' => 'Invite friends, earn credits', 'u' => '/referral', 'i' => '👥'],

            // Profile
            ['k' => 'profile', 't' => 'Health Profile', 's' => 'Update your health information', 'u' => '/onboarding', 'i' => '◉'],
            ['k' => 'onboarding', 't' => 'Health Profile', 's' => 'Update your health information', 'u' => '/onboarding', 'i' => '◉'],
            ['k' => 'health profile', 't' => 'Health Profile', 's' => 'Update your health information', 'u' => '/onboarding', 'i' => '◉'],
            ['k' => 'blood type', 't' => 'Health Profile', 's' => 'Update your blood type and health info', 'u' => '/onboarding', 'i' => '◉'],

            // Health Tools hub
            ['k' => 'health tools', 't' => 'Health Tools', 's' => 'Calculators & health trackers', 'u' => '/health-tools', 'i' => '◉'],
            ['k' => 'tools', 't' => 'Health Tools', 's' => 'Calculators & health trackers', 'u' => '/health-tools', 'i' => '◉'],
            ['k' => 'calculator', 't' => 'Health Tools', 's' => 'Calculators & health trackers', 'u' => '/health-tools', 'i' => '◉'],
            ['k' => 'tracker', 't' => 'Health Tools', 's' => 'Calculators & health trackers', 'u' => '/health-tools', 'i' => '◉'],

            // Individual health tools
            ['k' => 'bmi', 't' => 'BMI Calculator', 's' => 'Body mass index — check weight category', 'u' => '/health-tools/bmi', 'i' => '◉'],
            ['k' => 'body mass', 't' => 'BMI Calculator', 's' => 'Body mass index — check weight category', 'u' => '/health-tools/bmi', 'i' => '◉'],
            ['k' => 'weight', 't' => 'BMI Calculator', 's' => 'Body mass index calculator', 'u' => '/health-tools/bmi', 'i' => '◉'],
            ['k' => 'bmr', 't' => 'BMR Calculator', 's' => 'Basal metabolic rate — daily calorie burn', 'u' => '/health-tools/bmr', 'i' => '◉'],
            ['k' => 'metabolic', 't' => 'BMR Calculator', 's' => 'Basal metabolic rate calculator', 'u' => '/health-tools/bmr', 'i' => '◉'],
            ['k' => 'calorie', 't' => 'BMR Calculator', 's' => 'Basal metabolic rate — daily calorie burn', 'u' => '/health-tools/bmr', 'i' => '◉'],
            ['k' => 'due date', 't' => 'Due Date Calculator', 's' => 'Estimate your pregnancy due date', 'u' => '/health-tools/due-date', 'i' => '◉'],
            ['k' => 'pregnancy', 't' => 'Due Date Calculator', 's' => 'Estimate your pregnancy due date', 'u' => '/health-tools/due-date', 'i' => '◉'],
            ['k' => 'waist', 't' => 'Waist-Hip Calculator', 's' => 'Measure your waist-to-hip ratio', 'u' => '/health-tools/waist-hip', 'i' => '◉'],
            ['k' => 'hip', 't' => 'Waist-Hip Calculator', 's' => 'Measure your waist-to-hip ratio', 'u' => '/health-tools/waist-hip', 'i' => '◉'],
            ['k' => 'blood pressure', 't' => 'Blood Pressure Log', 's' => 'Track your BP readings over time', 'u' => '/health-tools/blood-pressure', 'i' => '⬤'],
            ['k' => 'bp log', 't' => 'Blood Pressure Log', 's' => 'Track your BP readings over time', 'u' => '/health-tools/blood-pressure', 'i' => '⬤'],
            ['k' => 'bp', 't' => 'Blood Pressure Log', 's' => 'Track your BP readings over time', 'u' => '/health-tools/blood-pressure', 'i' => '⬤'],
            ['k' => 'water', 't' => 'Water Intake Tracker', 's' => 'Track your daily water consumption', 'u' => '/health-tools/water', 'i' => '∼'],
            ['k' => 'hydration', 't' => 'Water Intake Tracker', 's' => 'Track your daily water consumption', 'u' => '/health-tools/water', 'i' => '∼'],
            ['k' => 'food diary', 't' => 'Food & Symptom Diary', 's' => 'Log meals and track symptoms', 'u' => '/health-tools/food-diary', 'i' => '●'],
            ['k' => 'food', 't' => 'Food & Symptom Diary', 's' => 'Log meals and track symptoms', 'u' => '/health-tools/food-diary', 'i' => '●'],
            ['k' => 'meal', 't' => 'Food & Symptom Diary', 's' => 'Log meals and track symptoms', 'u' => '/health-tools/food-diary', 'i' => '●'],
            ['k' => 'diet', 't' => 'Food & Symptom Diary', 's' => 'Log meals and track symptoms', 'u' => '/health-tools/food-diary', 'i' => '●'],
            ['k' => 'symptom log', 't' => 'Food & Symptom Diary', 's' => 'Log meals and track symptoms', 'u' => '/health-tools/food-diary', 'i' => '●'],
            ['k' => 'period', 't' => 'Period Tracker', 's' => 'Track your menstrual cycle', 'u' => '/health-tools/period', 'i' => '◉'],
            ['k' => 'menstrual', 't' => 'Period Tracker', 's' => 'Track your menstrual cycle', 'u' => '/health-tools/period', 'i' => '◉'],
            ['k' => 'cycle', 't' => 'Period Tracker', 's' => 'Track your menstrual cycle', 'u' => '/health-tools/period', 'i' => '◉'],
            ['k' => 'immunization', 't' => 'Immunization Tracker', 's' => 'Manage vaccine schedules', 'u' => '/health-tools/immunization', 'i' => '💉'],
            ['k' => 'vaccine', 't' => 'Immunization Tracker', 's' => 'Manage vaccine schedules', 'u' => '/health-tools/immunization', 'i' => '💉'],
            ['k' => 'vaccination', 't' => 'Immunization Tracker', 's' => 'Manage vaccine schedules', 'u' => '/health-tools/immunization', 'i' => '💉'],
            ['k' => 'appointment', 't' => 'Appointment Tracker', 's' => 'Manage your medical appointments', 'u' => '/health-tools/appointments', 'i' => '📅'],
            ['k' => 'appointments', 't' => 'Appointment Tracker', 's' => 'Manage your medical appointments', 'u' => '/health-tools/appointments', 'i' => '📅'],
            ['k' => 'schedule', 't' => 'Appointment Tracker', 's' => 'Manage your medical appointments', 'u' => '/health-tools/appointments', 'i' => '📅'],
            ['k' => 'visit', 't' => 'Appointment Tracker', 's' => 'Manage your medical appointments', 'u' => '/health-tools/appointments', 'i' => '📅'],
            ['k' => 'reminder', 't' => 'Appointment Tracker', 's' => 'Manage your medical appointments', 'u' => '/health-tools/appointments', 'i' => '📅'],
        ];
    }

    /**
     * Global search — returns panels, providers, blog posts, and in-app pages matching the query.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $q = trim($request->input('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $results = [];

        // 1. Search test panels
        try {
            $panels = \App\Models\TestPanel::where('name', 'like', "%{$q}%")
                ->orWhere('description', 'like', "%{$q}%")
                ->where('is_active', true)
                ->limit(5)
                ->get()
                ->map(fn($p) => [
                    'type' => 'panel',
                    'title' => $p->name,
                    'subtitle' => $p->description ?? 'Lab test panel',
                    'url' => '/lab-results/' . ($p->slug ?? ''),
                    'icon' => '⚛',
                ]);
            $results = array_merge($results, $panels->toArray());
        } catch (\Throwable $e) {
            // Panels table may not be available
        }

        // 2. Search user's own appointments
        try {
            $appointments = \App\Models\Appointment::where('user_id', $request->user()->id)
                ->where(function ($query) use ($q) {
                    $query->where('title', 'like', "%{$q}%")
                        ->orWhere('doctor_name', 'like', "%{$q}%")
                        ->orWhere('location', 'like', "%{$q}%")
                        ->orWhere('notes', 'like', "%{$q}%");
                })
                ->orderByDesc('appointment_date')
                ->limit(5)
                ->get()
                ->map(fn($a) => [
                    'type' => 'appointment',
                    'title' => $a->title ?? $a->doctor_name ?? 'Appointment',
                    'subtitle' => ($a->appointment_date ? date('M j, Y', strtotime($a->appointment_date)) : '') . ($a->location ? ' · ' . $a->location : ''),
                    'url' => '/health-tools/appointments',
                    'icon' => '📅',
                ]);
            $results = array_merge($results, $appointments->toArray());
        } catch (\Throwable $e) {
            // Appointments table may not exist
        }

        // 3. Search providers
        try {
            $providers = ProviderDirectoryEntry::where('is_active', true)
                ->where(function ($query) use ($q) {
                    $query->where('name', 'like', "%{$q}%")
                        ->orWhere('specialty', 'like', "%{$q}%")
                        ->orWhere('city', 'like', "%{$q}%")
                        ->orWhere('state', 'like', "%{$q}%");
                })
                ->limit(5)
                ->get()
                ->map(fn($p) => [
                    'type' => 'provider',
                    'title' => $p->name,
                    'subtitle' => $p->specialty . ' · ' . $p->city . ', ' . $p->state,
                    'url' => '/providers/' . ($p->slug ?? ''),
                    'icon' => '⚕',
                ]);
            $results = array_merge($results, $providers->toArray());
        } catch (\Throwable $e) {
            // Provider table may not be available
        }

        // 4. Search blog posts
        try {
            $posts = \App\Models\BlogPost::where('is_published', true)
                ->where(function ($query) use ($q) {
                    $query->where('title', 'like', "%{$q}%")
                        ->orWhere('excerpt', 'like', "%{$q}%")
                        ->orWhere('body', 'like', "%{$q}%");
                })
                ->limit(5)
                ->get()
                ->map(fn($p) => [
                    'type' => 'blog',
                    'title' => $p->title,
                    'subtitle' => $p->excerpt ?? 'Blog post',
                    'url' => '/blog/' . ($p->slug ?? ''),
                    'icon' => '📖',
                ]);
            $results = array_merge($results, $posts->toArray());
        } catch (\Throwable $e) {
            // Blog posts table may not be available
        }

        // 5. In-app navigation links (comprehensive fuzzy match)
        $qLower = mb_strtolower($q);
        $qWords = explode(' ', $qLower);
        $scored = [];

        foreach ($this->appPages() as $page) {
            $score = 0;
            $kw = $page['k'];

            // Exact match
            if ($kw === $qLower) {
                $score = 100;
            } elseif (mb_strpos($kw, $qLower) === 0) {
                // Starts with query
                $score = 80;
            } elseif (mb_strpos($kw, $qLower) !== false) {
                // Contains query
                $score = 60;
            } else {
                // Word-level fuzzy: if query words are found as substrings
                $wordHits = 0;
                foreach ($qWords as $word) {
                    if (mb_strlen($word) >= 2 && mb_strpos($kw, $word) !== false) {
                        $wordHits++;
                    }
                }
                if ($wordHits > 0) {
                    $score = 30 + ($wordHits * 10);
                }
            }

            if ($score > 0) {
                $scored[] = [
                    'score' => $score,
                    'type' => 'app',
                    'title' => $page['t'],
                    'subtitle' => $page['s'],
                    'url' => $page['u'],
                    'icon' => $page['i'],
                ];
            }
        }

        // Sort by score descending, deduplicate by URL, take top 8
        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);
        $seen = [];
        $appResults = [];
        foreach ($scored as $item) {
            $url = $item['url'];
            if (! isset($seen[$url])) {
                $seen[$url] = true;
                unset($item['score']);
                $appResults[] = $item;
                if (count($appResults) >= 8) break;
            }
        }

        $results = array_merge($results, $appResults);

        // Limit total
        $results = array_slice($results, 0, 20);

        return response()->json(['results' => $results]);
    }
}