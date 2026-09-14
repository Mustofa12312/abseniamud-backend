<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'check_in_event_id',
        'check_out_event_id',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function checkInEvent()
    {
        return $this->belongsTo(AttendanceEvent::class, 'check_in_event_id');
    }

    public function checkOutEvent()
    {
        return $this->belongsTo(AttendanceEvent::class, 'check_out_event_id');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isCheckedIn(): bool
    {
        return !is_null($this->check_in_event_id);
    }

    public function isCheckedOut(): bool
    {
        return !is_null($this->check_out_event_id);
    }
}
