<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class Otp extends Model
{
    use HasFactory;

    protected $table = 'otps';

    protected $fillable = [
        'phone',
        'otp_hash',
        'purpose',
        'attempts',
        'max_attempts',
        'expires_at',
        'verified_at',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'max_attempts' => 'integer',
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    /**
     * Scope for valid unverified OTPs.
     */
    public function scopeValid($query)
    {
        return $query->whereNull('verified_at')
            ->where('expires_at', '>', Carbon::now());
    }

    /**
     * Check if the OTP is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if maximum attempts exceeded.
     */
    public function hasExceededMaxAttempts(): bool
    {
        return $this->attempts >= $this->max_attempts;
    }

    /**
     * Verify the provided raw OTP code.
     */
    public function verifyCode(string $code): bool
    {
        $this->increment('attempts');

        if ($this->isExpired() || $this->hasExceededMaxAttempts()) {
            return false;
        }

        if (Hash::check($code, $this->otp_hash)) {
            $this->update(['verified_at' => Carbon::now()]);
            return true;
        }

        return false;
    }
}
