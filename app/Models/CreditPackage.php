<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditPackage extends Model
{
    protected $fillable = [
        'name', 'credits', 'price_kobo', 'currency', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'credits' => 'integer',
        'price_kobo' => 'integer',
        'is_active' => 'boolean',
    ];

    /** Append computed price attributes to JSON. */
    protected $appends = ['price_naira', 'price_formatted'];
    public function getPriceNairaAttribute(): float
    {
        return $this->price_kobo / 100;
    }

    public function getPriceFormattedAttribute(): string
    {
        return '₦' . number_format($this->price_naira, 0);
    }

    /** Price in Naira (for display). */
    public function priceNaira(): float
    {
        return $this->price_naira;
    }

    /** Format as ₦ with comma separator. */
    public function priceFormatted(): string
    {
        return $this->price_formatted;
    }
}

