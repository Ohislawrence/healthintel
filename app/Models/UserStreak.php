<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserStreak extends Model
{
    protected $fillable = ['user_id', 'streak_type', 'current_streak', 'longest_streak', 'last_activity_date'];
    protected $casts = ['last_activity_date' => 'date'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function recordActivity(string $date): void
    {
        $today = $date;
        $yesterday = date('Y-m-d', strtotime('-1 day', strtotime($today)));

        if ($this->last_activity_date?->format('Y-m-d') === $today) {
            return; // Already recorded today
        }

        if ($this->last_activity_date?->format('Y-m-d') === $yesterday) {
            $this->current_streak++;
        } else {
            $this->current_streak = 1;
        }

        $this->longest_streak = max($this->longest_streak, $this->current_streak);
        $this->last_activity_date = $today;
        $this->save();
    }
}