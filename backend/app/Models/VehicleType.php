<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleType extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_category_id',
        'name',
        'slug',
        'description',
        'icon',
        'image',
        'passenger_capacity',
        'load_capacity',
        'base_fare',
        'per_km_rate',
        'per_hour_rate',
        'minimum_fare',
        'status',
    ];

    protected $casts = [
        'passenger_capacity' => 'integer',
        'load_capacity' => 'decimal:2',
        'base_fare' => 'decimal:2',
        'per_km_rate' => 'decimal:2',
        'per_hour_rate' => 'decimal:2',
        'minimum_fare' => 'decimal:2',
    ];

    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
