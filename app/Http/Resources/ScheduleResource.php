<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'course_name' => $this->course->name ?? '-',
            'course_code' => $this->course->code ?? '-',
            'lecturer_name' => $this->course->lecturer->user->name ?? '-',
            'room_id' => $this->room_id,
            'room_name' => $this->room->name ?? '-',
            'day_of_week' => $this->day_of_week,
            'start_time' => \Carbon\Carbon::parse($this->start_time)->format('H:i'),
            'end_time' => \Carbon\Carbon::parse($this->end_time)->format('H:i'),
        ];
    }
}
