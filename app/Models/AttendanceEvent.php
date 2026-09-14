<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceEvent extends Model
{
    protected $fillable = [
        'user_id',
        'location_id',
        'type',
        'latitude',
        'longitude',
        'accuracy',
        'distance',
        'status',
        'reason',
        'event_time',
    ];

    protected $casts = [
        'event_time' => 'datetime',
        'latitude'   => 'float',
        'longitude'  => 'float',
        'accuracy'   => 'integer',
        'distance'   => 'integer',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isCheckIn(): bool
    {
        return $this->type === 'CHECK_IN';
    }

    public function isCheckOut(): bool
    {
        return $this->type === 'CHECK_OUT';
    }
}
