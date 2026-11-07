<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeePayment extends Model
{
    protected $fillable = [
        'student_fee_charge_id',
        'payment_receipt_id',
        'amount_kobo',
        'paid_at',
        'payment_method',
        'reference',
        'received_by',
    ];

    protected function casts(): array
    {
        return [
            'amount_kobo' => 'integer',
            'paid_at' => 'date',
        ];
    }

    public function charge(): BelongsTo
    {
        return $this->belongsTo(StudentFeeCharge::class, 'student_fee_charge_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
