<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    use CrudTrait, HasFactory;

    public const ROLE_STUDENT = 'SUDT';
    public const ROLE_PARENT = 'PT';
    public const ROLE_SECURITY = 'SET';
    public const ROLE_ADMIN = 'ADM';
    public const ROLE_FOUNDER = 'CEO';
    public const ROLE_ACCOUNTANT = 'ACC';
    public const ROLE_STAFF = 'STF';

    public const ROLES_MANAGE_ACCOUNTS = [self::ROLE_ADMIN, self::ROLE_FOUNDER];

    protected $table = 'persons';

    protected $fillable = [
        'sac_role_id',
        'user_id',
        'first_name',
        'last_name',
        'gender',
        'date_of_birth',
        'phone',
        'alt_email',
        'alt_phone',
        'Occupation',
        'address_line_1',
        'city',
        'state',
        'postal_code',
        'country',
        'nationality',
        'state_of_origin',
        'lga',
        'image_path',
    ];

    protected static function booted(): void
    {
        static::created(function (Person $person) {
            if ($person->sacRole?->code !== self::ROLE_STUDENT) {
                return;
            }

            // Built from the primary key, so concurrent inserts can't collide
            $person->student_id = sprintf('SCA/%s/%05d', now()->format('Y'), $person->id);
            $person->saveQuietly();
        });
    }

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'invited_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sacRole(): BelongsTo
    {
        return $this->belongsTo(SacRole::class, 'sac_role_id');
    }

    // Students this person (as a parent/guardian) is responsible for
    public function children(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'person_guardians', 'parent_id', 'student_id')
            ->withPivot(['relationship', 'is_primary_contact'])
            ->withTimestamps();
    }

    // Parents/guardians responsible for this person (as a student)
    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'person_guardians', 'student_id', 'parent_id')
            ->withPivot(['relationship', 'is_primary_contact'])
            ->withTimestamps();
    }

    public function pickupContactLinks(): HasMany
    {
        return $this->hasMany(ChildPickupContact::class, 'child_id')->orderBy('slot');
    }

    public function pickupContacts(): BelongsToMany
    {
        return $this->belongsToMany(
            PickupContact::class,
            'child_pickup_contacts',
            'child_id',
            'pickup_contact_id'
        )->withPivot(['slot', 'relationship', 'can_pick_up', 'can_drop_off'])
            ->withTimestamps();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'child_id');
    }

    public function getEmailAttribute(): ?string
    {
        return $this->user?->email;
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
