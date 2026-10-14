<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademyDocument extends Model
{
    protected $fillable = ['title', 'original_name', 'file_path'];
}
