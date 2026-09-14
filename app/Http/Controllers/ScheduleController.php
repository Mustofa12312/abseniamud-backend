<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Http\Requests\StoreScheduleRequest;
use App\Http\Requests\UpdateScheduleRequest;
use App\Services\ScheduleService;

class ScheduleController extends Controller
{
    protected $scheduleService;

    public function __construct(ScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

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

    public function store(StoreScheduleRequest $request)
    {
        $validated = $request->validated();
        
        if ($this->scheduleService->hasConflict(
            $validated['room_id'],
            $validated['day_of_week'],
            $validated['start_time'],
            $validated['end_time']
        )) {
            return response()->json([
                'success' => false,
                'message' => 'Konflik jadwal: Ruangan sudah digunakan pada waktu tersebut.'
            ], 422);
        }
        
        $schedule = Schedule::create($validated);
        $schedule->load(['course.lecturer.user', 'room']);
        
        return response()->json([
            'success' => true,
            'message' => 'Jadwal berhasil ditambahkan',
            'data' => $schedule
        ]);
    }

    public function update(UpdateScheduleRequest $request, $id)
    {
        $schedule = Schedule::findOrFail($id);
        $validated = $request->validated();

        if ($this->scheduleService->hasConflict(
            $validated['room_id'],
            $validated['day_of_week'],
            $validated['start_time'],
            $validated['end_time'],
            $schedule->id
        )) {
            return response()->json([
                'success' => false,
                'message' => 'Konflik jadwal: Ruangan sudah digunakan pada waktu tersebut.'
            ], 422);
        }

        $schedule->update($validated);
        $schedule->load(['course.lecturer.user', 'room']);

        return response()->json([
            'success' => true,
            'message' => 'Jadwal berhasil diperbarui',
            'data' => $schedule
        ]);
    }

    public function destroy($id)
    {
        Schedule::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Jadwal berhasil dihapus']);
    }
}
