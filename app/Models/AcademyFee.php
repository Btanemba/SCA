<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class AcademyFee extends Model
{
    use CrudTrait;

    protected $fillable = [
        'fee_name',
        'age_or_level',
        'amount',
        'schedule',
        'is_published',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}