<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    public const METHODS = ['cash', 'vnpay', 'momo'];

    public const STATUSES = ['unpaid', 'pending', 'paid', 'failed', 'cancelled', 'refunded'];

    protected $fillable = [
        'appointment_id',
        'user_id',
        'method',
        'status',
        'amount',
        'total_amount',
        'deposit_amount',
        'currency',
        'transaction_code',
        'gateway_order_id',
        'gateway_response',
        'paid_at',
        'deposit_paid_at',
    ];

    protected $appends = [
        'is_deposit_paid',
        'deposit_status_label',
        'remaining_amount',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isDepositPaid(): bool
    {
        return $this->status === 'paid' && $this->deposit_paid_at !== null;
    }

    public function getIsDepositPaidAttribute(): bool
    {
        return $this->isDepositPaid();
    }

    public function getDepositStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'paid' => 'Đã thanh toán',
            'pending' => 'Chưa thanh toán',
            'failed' => 'Thanh toán thất bại',
            'cancelled' => 'Đã hủy',
            'refunded' => 'Đã hoàn tiền',
            default => 'Chưa thanh toán',
        };
    }

    public function getRemainingAmountAttribute(): float
    {
        $totalAmount = (float) ($this->total_amount ?? $this->amount);
        $depositAmount = (float) ($this->deposit_amount ?? $this->amount);

        return max(0.0, $totalAmount - $depositAmount);
    }

    public static function depositAmountFor(float|int|string $totalAmount): float
    {
        $percent = max(0.0, min(100.0, (float) config('payments.deposit_percent', 30)));

        return round(((float) $totalAmount * $percent) / 100);
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'gateway_response' => 'array',
            'paid_at' => 'datetime',
            'deposit_paid_at' => 'datetime',
        ];
    }
}
