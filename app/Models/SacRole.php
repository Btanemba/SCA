<?php

namespace App\Models;

use App\Models\Concerns\TracksAuditUsers;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class SacRole extends Model
{
    use CrudTrait, TracksAuditUsers;

    protected $table = 'sac_role';

    protected $fillable = [
        'name',
        'code',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
        ];
    }

}
