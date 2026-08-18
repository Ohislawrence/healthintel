<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAnalyzerReport extends Model
{
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    protected $table = 'ai_analyzer_reports';

    protected $fillable = [
        'admin_id',
        'metrics',
        'analysis',
        'ai_available',
        'ai_error',
    ];

    protected function casts(): array
    {
        return [
            'metrics' => 'array',
            'analysis' => 'array',
            'ai_available' => 'boolean',
        ];
    }

    /**
     * Build the normalized payload shape used by the frontend.
     */
    public function toPayload(): array
    {
        return [
            'id' => $this->id,
            'metrics' => $this->metrics ?? [],
            'analysis' => $this->analysis ?? [],
            'ai_available' => $this->ai_available,
            'ai_error' => $this->ai_error,
            'generated_at' => $this->created_at?->toISOString(),
        ];
    }
}