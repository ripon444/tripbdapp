<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialSettlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'driver_id',
        'settlement_reference',
        'gross_fare',
        'platform_commission_percent',
        'platform_commission_amount',
        'driver_earning_amount',
        'service_charge_amount',
        'tax_amount',
        'discount_amount',
        'status',
        'settled_at',
    ];

    protected $casts = [
        'gross_fare' => 'decimal:2',
        'platform_commission_percent' => 'decimal:2',
        'platform_commission_amount' => 'decimal:2',
        'driver_earning_amount' => 'decimal:2',
        'service_charge_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'settled_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
