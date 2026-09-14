<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schedule;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $query = Schedule::with(['course.lecturer.user', 'room']);
        
        if ($request->has('day_of_week')) {
            $query->where('day_of_week', $request->day_of_week);
        }

        $schedules = $query->orderBy('start_time')->get()->map(function($s) {
            return [
                'id' => $s->id,
                'course_id' => $s->course_id,
                'course_name' => $s->course->name ?? 'Unknown',
                'lecturer_name' => $s->course->lecturer->user->name ?? 'Unknown',
                'room_id' => $s->room_id,
                'room_name' => $s->room->name ?? 'Unknown',
                'day_of_week' => $s->day_of_week,
                'start_time' => \Carbon\Carbon::parse($s->start_time)->format('H:i'),
                'end_time' => \Carbon\Carbon::parse($s->end_time)->format('H:i'),
            ];
        });

        // Group by day for easier frontend consumption
        $groupedSchedules = [
            'Senin' => [],
            'Selasa' => [],
            'Rabu' => [],
            'Kamis' => [],
            'Jumat' => [],
            'Sabtu' => [],
            'Minggu' => []
        ];

        foreach ($schedules as $s) {
            $groupedSchedules[$s['day_of_week']][] = $s;
        }

        return response()->json(['success' => true, 'data' => $groupedSchedules]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'room_id' => 'required|exists:rooms,id',
            'day_of_week' => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        $schedule = Schedule::create($validated);
        return response()->json(['success' => true, 'message' => 'Jadwal berhasil ditambahkan', 'data' => $schedule]);
    }

    public function update(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'room_id' => 'required|exists:rooms,id',
            'day_of_week' => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        $schedule->update($validated);
        return response()->json(['success' => true, 'message' => 'Jadwal berhasil diperbarui', 'data' => $schedule]);
    }

    public function destroy($id)
    {
        Schedule::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Jadwal berhasil dihapus']);
    }
}
