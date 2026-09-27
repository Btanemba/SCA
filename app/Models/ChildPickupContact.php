<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class ChildPickupContact extends Model
{
    protected $fillable = [
        'child_id',
        'pickup_contact_id',
        'slot',
        'relationship',
        'can_pick_up',
        'can_drop_off',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'can_pick_up' => 'boolean',
            'can_drop_off' => 'boolean',
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'child_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(PickupContact::class, 'pickup_contact_id');
    }
}
