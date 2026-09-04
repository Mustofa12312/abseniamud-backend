<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    protected $fillable = ['name', 'term', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
}
