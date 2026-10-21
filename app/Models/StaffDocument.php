<?php

namespace App\Models;

use App\Models\Concerns\TracksAuditUsers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffDocument extends Model
{
    use TracksAuditUsers;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'requires_staff_signature' => 'boolean',
            'academy_snapshot' => 'array',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->document_type) {
            'contract' => 'Contract',
            'employment' => 'Employment letter',
            'query' => 'Query letter',
            default => 'Other',
        };
    }
}
