<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'plan_id',
        'description',
        'units',
        'included_units',
        'overage_units',
        'rate',
        'amount',
        'period_start',
        'period_end',
    ];

    protected $casts = [
        'units' => 'integer',
        'included_units' => 'integer',
        'overage_units' => 'integer',
        'rate' => 'decimal:4',
        'amount' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}