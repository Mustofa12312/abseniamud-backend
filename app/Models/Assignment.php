<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    protected $fillable = [
        'lecturer_id',
        'position_id',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }
}
