<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;

class PickupContact extends Model
{
    protected $fillable = [
        'person_id',
        'first_name',
        'last_name',
        'image_path',
        'address',
        'email',
        'phone',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function rfidCards(): HasMany
    {
        return $this->hasMany(RfidCard::class);
    }

    public function activeRfidCard(): HasOne
    {
        return $this->hasOne(RfidCard::class)->whereNull('revoked_at')->latest('id');
    }

    public function childLinks(): HasMany
    {
        return $this->hasMany(ChildPickupContact::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
