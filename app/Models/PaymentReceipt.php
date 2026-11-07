<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class PaymentReceipt extends Model
{
    protected static function booted(): void
    {
        static::creating(function (PaymentReceipt $receipt) {
            $receipt->receipt_number = 'SCA-'.Str::upper(Str::random(24));
        });
    }

    protected $fillable = [
        'student_id',
        'amount_kobo',
        'paid_at',
        'payment_method',
        'reference',
        'received_by',
    ];

    protected function casts(): array
    {
        return ['amount_kobo' => 'integer', 'paid_at' => 'date'];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FeePayment::class, 'payment_receipt_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'student_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function walletCredit(): HasOne
    {
        return $this->hasOne(StudentWalletTransaction::class, 'payment_receipt_id')->where('type', 'credit');
    }
}
