<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentFeeCharge extends Model
{
    protected $fillable = [
        'student_id',
        'academy_fee_id',
        'billing_month',
        'fee_name',
        'amount_due_kobo',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'billing_month' => 'date',
            'amount_due_kobo' => 'integer',
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'student_id');
    }

    public function fee(): BelongsTo
    {
        return $this->belongsTo(AcademyFee::class, 'academy_fee_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FeePayment::class, 'student_fee_charge_id');
    }

    public function getPaidAmountKoboAttribute(): int
    {
        return (int) ($this->attributes['payments_sum_amount_kobo'] ?? $this->payments()->sum('amount_kobo'));
    }

    public function getBalanceKoboAttribute(): int
    {
        return max(0, $this->amount_due_kobo - $this->paid_amount_kobo);
    }

    public function getPaymentStatusAttribute(): string
    {
        if ($this->paid_amount_kobo >= $this->amount_due_kobo) {
            return 'paid';
        }

        return $this->paid_amount_kobo > 0 ? 'part_paid' : 'unpaid';
    }
}
