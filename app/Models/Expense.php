<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expense extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount_kobo' => 'integer',
            'expense_date' => 'date',
            'approval_snapshot' => 'array',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'paid_at' => 'date',
        ];
    }

    public function events(): HasMany
    {
        return $this->hasMany(ExpenseEvent::class);
    }

    public function editable(): bool
    {
        return in_array($this->status, ['draft', 'returned'], true);
    }

    public function approved(): bool
    {
        return in_array($this->status, ['approved', 'paid'], true);
    }

    public function getReferenceAttribute(): string
    {
        return sprintf('SCA/EXP/%04d/%06d', $this->created_at->year, $this->id);
    }
}
