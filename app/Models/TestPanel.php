<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestPanel extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'tests', 'is_active', 'sort_order'];

    protected $casts = [
        'tests' => 'array',
        'is_active' => 'boolean',
    ];

    public function ranges(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TestReferenceRange::class);
    }
}

