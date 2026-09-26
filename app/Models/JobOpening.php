<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class JobOpening extends Model
{
    use CrudTrait, HasFactory;

    public const EMPLOYMENT_TYPES = [
        'Full-time' => 'Full-time',
        'Part-time' => 'Part-time',
        'Contract' => 'Contract',
        'Temporary' => 'Temporary',
        'Internship' => 'Internship',
        'Volunteer' => 'Volunteer',
    ];

    protected $fillable = [
        'title',
        'slug',
        'department',
        'employment_type',
        'location',
        'salary_range',
        'summary',
        'description',
        'requirements',
        'closes_at',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'closes_at' => 'date',
            'is_published' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (JobOpening $opening) {
            if (blank($opening->slug)) {
                $opening->slug = Str::slug((string) $opening->title);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where(fn (Builder $q) => $q->whereNull('closes_at')->orWhereDate('closes_at', '>=', now()));
    }

    public function isOpen(): bool
    {
        $closesAt = $this->getAttributes()['closes_at'] ?? null;

        return $this->is_published && (! $closesAt || substr((string) $closesAt, 0, 10) >= now()->toDateString());
    }
}
