<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsenceNotice extends Model
{
    protected $fillable = [
        'child_id', 'absence_date', 'reason', 'reported_by', 'cancelled_at', 'cancelled_by',
    ];

    protected function casts(): array
    {
        return ['absence_date' => 'date', 'cancelled_at' => 'datetime'];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'child_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
