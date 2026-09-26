<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class SacRole extends Model
{
    use CrudTrait;

    protected $table = 'sac_role';

    protected $fillable = [
        'name',
        'code',
        'order',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
