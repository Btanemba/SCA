<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfidCard extends Model
{
    protected $fillable = [
        'pickup_contact_id',
        'uid',
        'revoked_at',
        'issued_by',
    ];

    protected function casts(): array
    {
        return [
            'revoked_at' => 'datetime',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(PickupContact::class, 'pickup_contact_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at');
    }

    public static function normalize(?string $uid): ?string
    {
        $uid = strtoupper((string) preg_replace('/[^0-9A-Za-z]/', '', (string) $uid));

        return $uid === '' ? null : $uid;
    }
}
