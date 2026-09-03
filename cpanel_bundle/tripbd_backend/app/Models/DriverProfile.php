<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DriverProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'profile_photo',
        'address',
        'city',
        'district',
        'nid_number',
        'license_number',
        'license_expiry',
        'verification_status',
        'online_status',
        'rating',
        'total_trips',
    ];

    protected $casts = [
        'license_expiry' => 'date',
        'rating' => 'decimal:2',
        'total_trips' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'driver_id');
    }

    public function activeVehicle(): HasOne
    {
        return $this->hasOne(Vehicle::class, 'driver_id')->where('status', 'active');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(DriverDocument::class, 'driver_id');
    }
}
