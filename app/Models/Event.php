<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use CrudTrait, HasFactory;

    protected $fillable = [
        'title',
        'description',
        'location',
        'starts_at',
        'ends_at',
        'images',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'images' => 'array',
            'is_published' => 'boolean',
        ];
    }
}
