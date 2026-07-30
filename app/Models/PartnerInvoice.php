<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerInvoice extends Model
{
    protected $fillable = [
        'partnership_id', 'invoice_number', 'period_start', 'period_end',
        'total_interpretations', 'included_in_allowance', 'billable_interpretations',
        'unit_price_kobo', 'subtotal_kobo', 'discount_kobo', 'total_kobo',
        'line_items', 'status', 'payment_id', 'paid_at', 'due_date', 'notes',
    ];

    protected $casts = [
        'line_items' => 'array',
        'paid_at' => 'datetime',
        'due_date' => 'date',
    ];

    public function partnership(): BelongsTo
    {
        return $this->belongsTo(LabPartnership::class, 'partnership_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function totalNaira(): float
    {
        return ($this->total_kobo ?? 0) / 100;
    }

    public function subtotalNaira(): float
    {
        return ($this->subtotal_kobo ?? 0) / 100;
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}