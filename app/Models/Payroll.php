<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'year' => 'integer',
            'approval_snapshot' => 'array',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function entries(): HasMany
    {
        return $this->hasMany(PayrollEntry::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(PayrollEvent::class);
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
        return sprintf('SCA/PAY/%04d/%02d/%06d', $this->year, $this->month, $this->id);
    }
}
