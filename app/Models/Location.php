<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'radius',
        'max_accuracy',
        'is_active',
    ];

    protected $casts = [
        'latitude'     => 'float',
        'longitude'    => 'float',
        'radius'       => 'integer',
        'max_accuracy' => 'integer',
        'is_active'    => 'boolean',
    ];

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function attendanceEvents()
    {
        return $this->hasMany(AttendanceEvent::class);
    }
}
