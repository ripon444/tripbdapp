<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Withdrawal extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'wallet_id',
        'withdrawal_number',
        'amount',
        'method',
        'account_number',
        'bank_name',
        'branch_name',
        'routing_number',
        'status',
        'admin_note',
        'processed_by',
        'processed_at',
        'completed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Return masked account number for privacy and PCI/security compliance.
     */
    public function getMaskedAccountNumberAttribute(): string
    {
        $acc = (string) $this->account_number;
        if (strlen($acc) <= 4) {
            return str_repeat('*', strlen($acc));
        }
        return substr($acc, 0, 2) . str_repeat('*', max(1, strlen($acc) - 5)) . substr($acc, -3);
    }
}
