<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Lecturer;
use App\Models\Location;
use App\Models\AttendanceRecord;
use App\Models\AttendanceCorrection;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\Room;
use App\Models\Faculty;
use App\Models\Course;
use App\Models\AcademicYear;
use Carbon\Carbon;
use App\Http\Requests\StoreLocationRequest;
use App\Http\Requests\UpdateLocationRequest;
use App\Http\Requests\StoreLecturerRequest;
use App\Http\Requests\UpdateLecturerRequest;
use App\Http\Resources\LocationResource;
use App\Http\Resources\LecturerResource;
use App\Http\Resources\CorrectionResource;
use App\Http\Resources\AuditLogResource;
use App\Services\AuditLogService;

class AdminController extends Controller
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Get dashboard statistics.
     */
    public function dashboard()
    {
        $today = Carbon::today();
        
        $totalDosen = Lecturer::count();
        
        // Count how many have checked in today
        $hadirHariIni = AttendanceRecord::where('date', $today)
                            ->where('status', '!=', 'TIDAK_HADIR')
                            ->count();
                            
        $terlambat = AttendanceRecord::where('date', $today)
                            ->where('status', 'TERLAMBAT')
                            ->count();
                            
        $lokasiAktif = Location::where('is_active', true)->count();

        // Recent activity (mocked structure, getting from AttendanceRecord/Event in real world)
        // For simplicity, we just pull the recent records
        $recentActivity = AttendanceRecord::with('user')
                            ->where('date', $today)
                            ->orderBy('updated_at', 'desc')
                            ->take(5)
                            ->get()
                            ->map(function($record) {
                                return [
                                    'name' => $record->user->name ?? 'Unknown',
                                    'action' => 'Check-in/out',
                                    'time' => $record->updated_at->format('H:i'),
                                    'location' => 'Kampus', // would link to event's location
                                    'late' => $record->status === 'TERLAMBAT'
                                ];
                            });

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    ['title' => 'Total Dosen', 'value' => $totalDosen],
                    ['title' => 'Hadir Hari Ini', 'value' => $hadirHariIni],
                    ['title' => 'Terlambat', 'value' => $terlambat],
                    ['title' => 'Lokasi Aktif', 'value' => $lokasiAktif],
                ],
                'recent_activity' => $recentActivity
            ]
        ]);
    }

    /**
     * Get attendance monitoring data.
     */
    public function attendance(Request $request)
    {
        $date = $request->query('date', Carbon::today()->toDateString());
        
        $records = AttendanceRecord::with(['user', 'checkInEvent.location', 'checkOutEvent'])
                        ->where('date', $date)
                        ->get()
                        ->map(function($record) {
                            return [
                                'id' => $record->id,
                                'name' => $record->user->name ?? 'Unknown',
                                'checkIn' => $record->checkInEvent ? Carbon::parse($record->checkInEvent->event_time)->format('H:i') : '-',
                                'checkOut' => $record->checkOutEvent ? Carbon::parse($record->checkOutEvent->event_time)->format('H:i') : '-',
                                'location' => $record->checkInEvent->location->name ?? '-',
                                'status' => $record->status,
                            ];
                        });
                        
        // Just for demo: include all lecturers even if they haven't checked in
        // Real implementation might involve a left join or mapping from lecturers table
        $allLecturers = Lecturer::with('user')->get();
        $formattedData = [];
        
        foreach ($allLecturers as $lecturer) {
            $user = $lecturer->user;
            if (!$user) continue;
            
            $record = $records->firstWhere('name', $user->name);
            if ($record) {
                $statusColor = $record['status'] === 'HADIR' ? 'success' : ($record['status'] === 'TERLAMBAT' ? 'warning' : 'default');
                $formattedData[] = array_merge($record, ['statusColor' => $statusColor]);
            } else {
                $formattedData[] = [
                    'id' => 'u'.$user->id,
                    'name' => $user->name,
                    'checkIn' => '-',
                    'checkOut' => '-',
                    'location' => '-',
                    'status' => 'Belum Absen',
                    'statusColor' => 'default'
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $formattedData,
            'date' => Carbon::parse($date)->translatedFormat('d F Y')
        ]);
    }

    /**
     * Get attendance details for a specific lecturer in a month.
     */
    public function attendanceDetails(Request $request, $id)
    {
        $lecturer = Lecturer::with('user')->findOrFail($id);
        $user = $lecturer->user;
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $month = $request->query('month', Carbon::now()->month);
        $year = $request->query('year', Carbon::now()->year);

        $records = AttendanceRecord::with(['checkInEvent.location', 'checkOutEvent'])
            ->where('user_id', $user->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($record) {
                return [
                    'id'       => $record->id,
                    'date'     => Carbon::parse($record->date)->translatedFormat('d M Y'),
                    'raw_date' => $record->date->toDateString(),
                    'checkIn'  => $record->checkInEvent ? Carbon::parse($record->checkInEvent->event_time)->format('H:i') : '-',
                    'checkOut' => $record->checkOutEvent ? Carbon::parse($record->checkOutEvent->event_time)->format('H:i') : '-',
                    'location' => $record->checkInEvent?->location?->name ?? '-',
                    'status'   => $record->status,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $records,
            'lecturer' => [
                'name' => $user->name,
                'nidn' => $lecturer->nidn
            ],
            'period' => Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y')
        ]);
    }



    /**
     * Get attendance report data.
     */
    public function reports(Request $request)
    {
        $month = $request->query('month', Carbon::now()->month);
        $year = $request->query('year', Carbon::now()->year);

        // Simple aggregation logic for the report
        $records = AttendanceRecord::whereMonth('date', $month)
                        ->whereYear('date', $year)
                        ->get();

        $lecturers = Lecturer::with('user')->get();
        $reportData = [];

        // Calculate working days dynamically (excluding Sundays)
        $startOfMonth = Carbon::create($year, $month, 1);
        $daysInMonth = $startOfMonth->daysInMonth;
        $totalDays = 0;
        
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $currentDate = Carbon::create($year, $month, $d);
            if (!$currentDate->isSunday()) {
                $totalDays++;
            }
        }
        
        if ($totalDays === 0) $totalDays = 1; // Prevent division by zero

        foreach ($lecturers as $lecturer) {
            $user = $lecturer->user;
            if (!$user) continue;

            $userRecords = $records->where('user_id', $user->id);
            
            $hadir = $userRecords->where('status', 'HADIR')->count();
            $terlambat = $userRecords->where('status', 'TERLAMBAT')->count();
            $alpha = $totalDays - ($hadir + $terlambat); // Alpha is remaining days without presence
            if ($alpha < 0) $alpha = 0;

            $reportData[] = [
                'id' => $lecturer->id,
                'name' => $user->name,
                'nidn' => $lecturer->nidn ?? '-',
                'hadir' => $hadir,
                'terlambat' => $terlambat,
                'alpha' => $alpha,
                'persentase' => round((($hadir + $terlambat) / $totalDays) * 100) . '%'
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $reportData,
            'period' => Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y')
        ]);
    }

    // Schedules moved to ScheduleController

    /**
     * Get system settings.
     */
    public function settings()
    {
        $settings = Setting::all();

        // If settings are empty (not seeded yet), provide defaults
        if ($settings->isEmpty()) {
            $defaultSettings = [
                ['key' => 'app_name', 'value' => 'IAIMU Attendance', 'type' => 'string', 'description' => 'Nama Aplikasi'],
                ['key' => 'default_checkin_time', 'value' => '07:00', 'type' => 'time', 'description' => 'Jam Masuk Standar'],
                ['key' => 'late_tolerance_minutes', 'value' => '15', 'type' => 'integer', 'description' => 'Batas Keterlambatan (Menit)'],
                ['key' => 'default_radius_meters', 'value' => '50', 'type' => 'integer', 'description' => 'Radius Presensi (Meter)']
            ];
            foreach ($defaultSettings as $ds) {
                Setting::create($ds);
            }
            $settings = Setting::all();
        }

        $formattedSettings = [];
        foreach ($settings as $s) {
            $formattedSettings[$s->key] = $s->value;
        }

        return response()->json([
            'success' => true,
            'data' => $formattedSettings
        ]);
    }

    /**
     * Update system settings.
     */
    public function updateSettings(Request $request)
    {
        $data = $request->all();
        
        foreach ($data as $key => $value) {
            Setting::where('key', $key)->update(['value' => $value]);
        }

        // Record Audit Log
        $this->auditLogService->log(
            $request->user()->id,
            'UPDATE_SETTINGS',
            'System Settings',
            $data
        );

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan berhasil disimpan.'
        ]);
    }

    /**
     * Get all corrections for admin.
     */
    public function corrections(Request $request)
    {
        $query = AttendanceCorrection::with('user')->orderBy('created_at', 'desc');

        // Optional filter
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Pagination
        $perPage = $request->input('per_page', 10);
        $corrections = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => CorrectionResource::collection($corrections->items()),
            'meta' => [
                'current_page' => $corrections->currentPage(),
                'last_page' => $corrections->lastPage(),
                'per_page' => $corrections->perPage(),
                'total' => $corrections->total()
            ]
        ]);
    }

    /**
     * Approve correction.
     */
    public function approveCorrection(Request $request, $id)
    {
        $correction = AttendanceCorrection::findOrFail($id);
        
        if ($correction->status !== 'PENDING') {
            return response()->json(['success' => false, 'message' => 'Status sudah diproses sebelumnya.'], 400);
        }

        $correction->update([
            'status' => 'APPROVED',
            'admin_id' => $request->user()->id
        ]);

        // Update attendance record
        AttendanceRecord::updateOrCreate(
            ['user_id' => $correction->user_id, 'date' => $correction->date],
            ['status' => 'HADIR'] // Simplifying logic: approval grants HADIR status
        );

        // Record Audit Log
        $this->auditLogService->log(
            $request->user()->id,
            'APPROVE_CORRECTION',
            'Correction ID: ' . $id . ' | User ID: ' . $correction->user_id,
            [
                'reason' => $correction->reason,
                'date' => $correction->date,
                'type' => $correction->type
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Koreksi berhasil disetujui.'
        ]);
    }

    /**
     * Reject correction.
     */
    public function rejectCorrection(Request $request, $id)
    {
        $correction = AttendanceCorrection::findOrFail($id);
        
        if ($correction->status !== 'PENDING') {
            return response()->json(['success' => false, 'message' => 'Status sudah diproses sebelumnya.'], 400);
        }

        $correction->update([
            'status' => 'REJECTED',
            'admin_id' => $request->user()->id
        ]);

        // Record Audit Log
        $this->auditLogService->log(
            $request->user()->id,
            'REJECT_CORRECTION',
            'Correction ID: ' . $id . ' | User ID: ' . $correction->user_id,
            [
                'reason' => $correction->reason,
                'date' => $correction->date
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Koreksi berhasil ditolak.'
        ]);
    }

    /**
     * Get all audit logs for admin.
     */
    public function auditLogs(Request $request)
    {
        $query = AuditLog::with('user')->orderBy('created_at', 'desc');

        if ($request->has('action') && $request->action) {
            $query->where('action', $request->action);
        }

        $perPage = $request->input('per_page', 20);
        $logs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => AuditLogResource::collection($logs->items()),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total()
            ]
        ]);
    }
}
