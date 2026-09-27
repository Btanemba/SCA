<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use CrudTrait;

    protected $fillable = [
        'child_id',
        'attendance_date',
        'dropped_off_at',
        'dropped_off_by_contact_id',
        'dropped_off_by_name',
        'dropped_off_recorded_by',
        'picked_up_at',
        'picked_up_by_contact_id',
        'picked_up_by_name',
        'picked_up_recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'dropped_off_at' => 'datetime',
            'picked_up_at' => 'datetime',
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'child_id');
    }

    public function droppedOffBy(): BelongsTo
    {
        return $this->belongsTo(PickupContact::class, 'dropped_off_by_contact_id');
    }

    public function pickedUpBy(): BelongsTo
    {
        return $this->belongsTo(PickupContact::class, 'picked_up_by_contact_id');
    }

    public function droppedOffRecordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dropped_off_recorded_by');
    }

    public function pickedUpRecordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'picked_up_recorded_by');
    }

    public function getIsPresentAttribute(): bool
    {
        return $this->dropped_off_at !== null && $this->picked_up_at === null;
    }

    public function getStatusAttribute(): string
    {
        return $this->is_present ? 'Present' : 'Picked up';
    }
}
