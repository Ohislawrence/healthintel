<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BenchmarkRun extends Model
{
    protected $table = 'benchmark_runs';

    protected $fillable = [
        'name',
        'dataset_version',
        'total_questions',
        'correct_answers',
        'accuracy',
        'avg_response_time_ms',
        'model_used',
        'specialty_breakdown',
        'difficulty_breakdown',
        'detailed_results',
        'status',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'specialty_breakdown' => 'json',
        'difficulty_breakdown' => 'json',
        'detailed_results' => 'json',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'accuracy' => 'decimal:2',
        'avg_response_time_ms' => 'decimal:2',
    ];

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function getAccuracyFormattedAttribute(): string
    {
        return $this->accuracy ? number_format($this->accuracy, 1) . '%' : 'N/A';
    }
}