<?php

namespace App\Services;

use App\Models\Schedule;

class ScheduleService
{
    /**
     * Check if a schedule conflicts with an existing one.
     * Conflict happens if the same room is used on the same day with overlapping time.
     *
     * @param int $roomId
     * @param string $dayOfWeek
     * @param string $startTime
     * @param string $endTime
     * @param int|null $ignoreScheduleId
     * @return bool
     */
    public function hasConflict(int $roomId, string $dayOfWeek, string $startTime, string $endTime, ?int $ignoreScheduleId = null): bool
    {
        $query = Schedule::where('room_id', $roomId)
            ->where('day_of_week', $dayOfWeek)
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where(function ($q2) use ($startTime, $endTime) {
                    $q2->whereTime('start_time', '<', $endTime)
                       ->whereTime('end_time', '>', $startTime);
                });
            });

        if ($ignoreScheduleId) {
            $query->where('id', '!=', $ignoreScheduleId);
        }

        return $query->exists();
    }
}
