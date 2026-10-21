<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin Model
 * @property-read User|null $creator
 * @property-read User|null $updater
 * @method static void creating(\Closure $callback)
 * @method static void updating(\Closure $callback)
 */
trait TracksAuditUsers
{
    public static function bootTracksAuditUsers(): void
    {
        static::creating(function ($model): void {
            $userId = backpack_user()?->getAuthIdentifier();

            if ($userId !== null) {
                $model->created_by = $userId;

                if ($model->tracksUpdater()) {
                    $model->updated_by = $userId;
                }
            }
        });

        static::updating(function ($model): void {
            $model->created_by = $model->getRawOriginal('created_by');
            $userId = backpack_user()?->getAuthIdentifier();

            if ($model->tracksUpdater()) {
                $model->updated_by = $userId ?? $model->getRawOriginal('updated_by');
            }
        });
    }

    protected function tracksUpdater(): bool
    {
        return true;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getCreatedByNameAttribute(): ?string
    {
        return $this->creator?->person?->full_name ?: $this->creator?->name;
    }

    public function getUpdatedByNameAttribute(): ?string
    {
        return $this->updater?->person?->full_name ?: $this->updater?->name;
    }
}
