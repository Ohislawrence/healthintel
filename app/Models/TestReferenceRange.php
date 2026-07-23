<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestReferenceRange extends Model
{
    protected $fillable = [
        'test_panel_id', 'test_slug', 'test_name', 'unit', 'decimals',
        'range_low_male', 'range_high_male',
        'range_low_female', 'range_high_female',
        'range_low_pregnant', 'range_high_pregnant',
        'range_low_pediatric', 'range_high_pediatric',
        'critical_low', 'critical_high',
        'source',
    ];

    protected $casts = [
        'decimals' => 'integer',
        'range_low_male' => 'decimal:4',
        'range_high_male' => 'decimal:4',
        'range_low_female' => 'decimal:4',
        'range_high_female' => 'decimal:4',
        'range_low_pregnant' => 'decimal:4',
        'range_high_pregnant' => 'decimal:4',
        'range_low_pediatric' => 'decimal:4',
        'range_high_pediatric' => 'decimal:4',
        'critical_low' => 'decimal:4',
        'critical_high' => 'decimal:4',
    ];
}
