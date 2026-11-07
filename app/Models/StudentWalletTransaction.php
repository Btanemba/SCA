<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentWalletTransaction extends Model
{
    protected $fillable = [
        'student_id',
        'type',
        'amount_kobo',
        'payment_receipt_id',
        'fee_payment_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return ['amount_kobo' => 'integer'];
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(PaymentReceipt::class, 'payment_receipt_id');
    }

    public function feePayment(): BelongsTo
    {
        return $this->belongsTo(FeePayment::class, 'fee_payment_id');
    }
}
