<?php
namespace App\Services;

use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserStreak;

class GamificationService
{
    private array $badgeDefinitions = [
        'first_steps' => ['name' => 'First Steps', 'desc' => 'Completed your health profile', 'emoji' => '🦶'],
        'tracker' => ['name' => 'Tracker', 'desc' => 'Logged 7 consecutive days of health tracking', 'emoji' => '📊'],
        'insight_seeker' => ['name' => 'Insight Seeker', 'desc' => 'Submitted 5 lab interpretations', 'emoji' => '🔬'],
        'trend_watcher' => ['name' => 'Trend Watcher', 'desc' => 'Viewed trends for 3+ biomarkers', 'emoji' => '📈'],
        'referral_hero' => ['name' => 'Referral Hero', 'desc' => 'Referred 3+ people to HealthIntel', 'emoji' => '🦸'],
    ];

    public function awardBadge(User $user, string $badgeKey): ?UserBadge
    {
        if (!isset($this->badgeDefinitions[$badgeKey])) return null;
        $existing = UserBadge::where('user_id', $user->id)->where('badge_key', $badgeKey)->first();
        if ($existing) return $existing;
        $def = $this->badgeDefinitions[$badgeKey];
        return UserBadge::create(['user_id' => $user->id, 'badge_key' => $badgeKey, 'badge_name' => $def['name'], 'badge_description' => $def['desc']]);
    }

    public function recordTrackerActivity(User $user, string $date = null): void
    {
        $streak = UserStreak::firstOrCreate(['user_id' => $user->id, 'streak_type' => 'daily_tracker'], ['current_streak' => 0, 'longest_streak' => 0, 'last_activity_date' => null]);
        $streak->recordActivity($date ?? now()->toDateString());
        if ($streak->current_streak >= 7) $this->awardBadge($user, 'tracker');
    }

    public function recordLabActivity(User $user): void
    {
        $streak = UserStreak::firstOrCreate(['user_id' => $user->id, 'streak_type' => 'weekly_lab'], ['current_streak' => 0, 'longest_streak' => 0, 'last_activity_date' => null]);
        $streak->recordActivity(now()->toDateString());
        $count = $user->labSubmissions()->count();
        if ($count >= 5) $this->awardBadge($user, 'insight_seeker');
    }

    public function recordProfileComplete(User $user): void
    {
        $this->awardBadge($user, 'first_steps');
    }

    public function recordTrendViewed(User $user, int $uniqueBiomarkersViewed): void
    {
        if ($uniqueBiomarkersViewed >= 3) $this->awardBadge($user, 'trend_watcher');
    }

    public function recordReferral(User $user, int $totalReferrals): void
    {
        if ($totalReferrals >= 3) $this->awardBadge($user, 'referral_hero');
    }

    public function getUserGamification(User $user): array
    {
        $badges = UserBadge::where('user_id', $user->id)->orderBy('awarded_at')->get();
        $streaks = UserStreak::where('user_id', $user->id)->get();
        $allBadges = [];
        foreach ($this->badgeDefinitions as $key => $def) {
            $earned = $badges->firstWhere('badge_key', $key);
            $allBadges[] = ['key' => $key, 'name' => $def['name'], 'desc' => $def['desc'], 'emoji' => $def['emoji'], 'earned' => !is_null($earned), 'earned_at' => $earned?->awarded_at];
        }
        return ['badges' => $allBadges, 'streaks' => $streaks->map(fn($s) => ['type' => $s->streak_type, 'current' => $s->current_streak, 'longest' => $s->longest_streak])];
    }
}