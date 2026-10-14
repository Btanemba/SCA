<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class StaffMember extends Model
{
    use CrudTrait, HasFactory;

    protected $fillable = [
        'person_id',
        'description',
        'sort_order',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function getStaffNameAttribute(): string
    {
        return $this->person?->full_name ?? '';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

}
