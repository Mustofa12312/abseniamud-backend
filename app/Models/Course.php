<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = ['faculty_id', 'lecturer_id', 'name', 'code', 'semester', 'sks'];

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class);
    }
}
