<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Symptom extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'category', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function testPanels(): BelongsToMany
    {
        return $this->belongsToMany(TestPanel::class, 'symptom_test_panels')
            ->withPivot('relevance_score')
            ->withTimestamps();
    }
}
