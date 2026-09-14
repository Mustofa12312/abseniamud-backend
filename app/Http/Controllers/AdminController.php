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

class AdminController extends Controller
{
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
     * Get locations.
     */
    public function locations()
    {
        $locations = Location::all()->map(function($loc) {
            return [
                'id' => $loc->id,
                'name' => $loc->name,
                'lat' => $loc->latitude,
                'lng' => $loc->longitude,
                'radius' => $loc->radius,
                'accuracy' => $loc->max_accuracy,
                'is_active' => $loc->is_active,
                'status' => $loc->is_active ? 'Aktif' : 'Nonaktif'
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $locations
        ]);
    }

    public function storeLocation(StoreLocationRequest $request)
    {
        $validated = $request->validated();

        $location = Location::create($validated);
        return response()->json(['success' => true, 'message' => 'Lokasi berhasil ditambahkan.', 'data' => $location]);
    }

    public function updateLocation(UpdateLocationRequest $request, $id)
    {
        $location = Location::findOrFail($id);
        $validated = $request->validated();

        $location->update($validated);
        return response()->json(['success' => true, 'message' => 'Lokasi berhasil diperbarui.', 'data' => $location]);
    }

    public function destroyLocation($id)
    {
        Location::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Lokasi berhasil dihapus.']);
    }

    /**
     * Get all lecturers data.
     */
    public function lecturers()
    {
        $lecturers = Lecturer::with('user')->get()->map(function($lec) {
            return [
                'id' => $lec->id,
                'name' => $lec->user->name ?? 'Unknown',
                'email' => $lec->user->email ?? '-',
                'nidn' => $lec->nidn ?? '-',
                'nip' => $lec->nip ?? '-',
                'phone' => $lec->phone ?? '-',
                'address' => $lec->address ?? '-',
                'user_id' => $lec->user_id
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $lecturers
        ]);
    }

    public function storeLecturer(StoreLecturerRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'role_id' => 3 // Assuming 3 is dosen
        ]);

        $lecturer = Lecturer::create([
            'user_id' => $user->id,
            'nidn' => $validated['nidn'] ?? null,
            'nip' => $validated['nip'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
        ]);

        return response()->json(['success' => true, 'message' => 'Dosen berhasil ditambahkan.', 'data' => $lecturer]);
    }

    public function updateLecturer(UpdateLecturerRequest $request, $id)
    {
        $lecturer = Lecturer::findOrFail($id);
        $user = $lecturer->user;

        $validated = $request->validated();

        if ($user) {
            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);
            
            if ($request->filled('password')) {
                $user->update(['password' => \Illuminate\Support\Facades\Hash::make($request->password)]);
            }
        }

        $lecturer->update([
            'nidn' => $validated['nidn'] ?? null,
            'nip' => $validated['nip'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
        ]);

        return response()->json(['success' => true, 'message' => 'Dosen berhasil diperbarui.', 'data' => $lecturer]);
    }

    public function destroyLecturer($id)
    {
        $lecturer = Lecturer::findOrFail($id);
        if ($lecturer->user) {
            $lecturer->user->delete();
        }
        // Lecturer will be deleted due to cascade
        return response()->json(['success' => true, 'message' => 'Dosen berhasil dihapus.']);
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

        foreach ($lecturers as $lecturer) {
            $user = $lecturer->user;
            if (!$user) continue;

            $userRecords = $records->where('user_id', $user->id);
            
            $hadir = $userRecords->where('status', 'HADIR')->count();
            $terlambat = $userRecords->where('status', 'TERLAMBAT')->count();
            $alpha = $userRecords->where('status', 'TIDAK_HADIR')->count();
            // Just simulate total working days for a month
            $totalDays = 22;

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
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'UPDATE_SETTINGS',
            'target' => 'System Settings',
            'details' => $data,
            'ip_address' => $request->ip()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan berhasil disimpan.'
        ]);
    }

    /**
     * Get all corrections for admin.
     */
    public function corrections()
    {
        $corrections = AttendanceCorrection::with('user')->orderBy('created_at', 'desc')->get()->map(function($c) {
            return [
                'id' => $c->id,
                'name' => $c->user->name ?? 'Unknown',
                'date' => Carbon::parse($c->date)->translatedFormat('d M Y'),
                'raw_date' => $c->date,
                'type' => $c->type,
                'reason' => $c->reason,
                'status' => $c->status,
                'submitted_at' => $c->created_at->format('d/m/Y H:i'),
                'user_id' => $c->user_id
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $corrections
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
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'APPROVE_CORRECTION',
            'target' => 'Correction ID: ' . $id . ' | User ID: ' . $correction->user_id,
            'details' => [
                'reason' => $correction->reason,
                'date' => $correction->date,
                'type' => $correction->type
            ],
            'ip_address' => $request->ip()
        ]);

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
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'REJECT_CORRECTION',
            'target' => 'Correction ID: ' . $id . ' | User ID: ' . $correction->user_id,
            'details' => [
                'reason' => $correction->reason,
                'date' => $correction->date
            ],
            'ip_address' => $request->ip()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Koreksi berhasil ditolak.'
        ]);
    }

    /**
     * Get all audit logs for admin.
     */
    public function auditLogs()
    {
        $logs = AuditLog::with('user')->orderBy('created_at', 'desc')->get()->map(function($l) {
            return [
                'id' => $l->id,
                'admin_name' => $l->user->name ?? 'System',
                'action' => $l->action,
                'target' => $l->target,
                'details' => $l->details,
                'ip_address' => $l->ip_address,
                'created_at' => $l->created_at->format('d/m/Y H:i:s')
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    /**
     * Get all rooms.
     */
    public function rooms()
    {
        $rooms = Room::orderBy('name')->get();
        return response()->json([
            'success' => true,
            'data' => $rooms
        ]);
    }

    /**
     * Store a new room.
     */
    public function storeRoom(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'nullable|integer',
            'description' => 'nullable|string'
        ]);

        $room = Room::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Ruangan berhasil ditambahkan.',
            'data' => $room
        ]);
    }

    /**
     * Update room.
     */
    public function updateRoom(Request $request, $id)
    {
        $room = Room::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'nullable|integer',
            'description' => 'nullable|string'
        ]);

        $room->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Ruangan berhasil diperbarui.',
            'data' => $room
        ]);
    }

    /**
     * Delete room.
     */
    public function destroyRoom($id)
    {
        $room = Room::findOrFail($id);
        $room->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ruangan berhasil dihapus.'
        ]);
    }

    /**
     * Get all faculties.
     */
    public function faculties()
    {
        $faculties = Faculty::orderBy('name')->get();
        return response()->json([
            'success' => true,
            'data' => $faculties
        ]);
    }

    public function storeFaculty(Request $request)
    {
        $request->validate(['name' => 'required|string', 'code' => 'nullable|string']);
        $faculty = Faculty::create($request->all());
        return response()->json(['success' => true, 'message' => 'Fakultas / Prodi berhasil ditambahkan.', 'data' => $faculty]);
    }

    public function updateFaculty(Request $request, $id)
    {
        $faculty = Faculty::findOrFail($id);
        $request->validate(['name' => 'required|string', 'code' => 'nullable|string']);
        $faculty->update($request->all());
        return response()->json(['success' => true, 'message' => 'Fakultas / Prodi berhasil diperbarui.', 'data' => $faculty]);
    }

    public function destroyFaculty($id)
    {
        Faculty::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Fakultas / Prodi berhasil dihapus.']);
    }

    /**
     * Get all courses.
     */
    public function courses(Request $request)
    {
        $query = Course::with(['faculty', 'lecturer.user']);
        
        if ($request->has('faculty_id') && $request->faculty_id) {
            $query->where('faculty_id', $request->faculty_id);
        }
        
        if ($request->has('semester') && $request->semester) {
            $query->where('semester', $request->semester);
        }

        $courses = $query->orderBy('name')->get()->map(function($course) {
            $courseArray = $course->toArray();
            $courseArray['lecturer_name'] = $course->lecturer ? ($course->lecturer->user->name ?? 'Unknown') : null;
            return $courseArray;
        });
        
        return response()->json([
            'success' => true,
            'data' => $courses
        ]);
    }

    public function storeCourse(Request $request)
    {
        $request->validate([
            'faculty_id' => 'required|exists:faculties,id',
            'lecturer_id' => 'nullable|exists:lecturers,id',
            'name' => 'required|string',
            'code' => 'nullable|string',
            'semester' => 'required|integer',
            'sks' => 'required|integer'
        ]);
        $course = Course::create($request->all());
        $course->load('lecturer.user');
        $courseArray = $course->toArray();
        $courseArray['lecturer_name'] = $course->lecturer ? ($course->lecturer->user->name ?? 'Unknown') : null;
        return response()->json(['success' => true, 'message' => 'Mata Kuliah berhasil ditambahkan.', 'data' => $courseArray]);
    }

    public function updateCourse(Request $request, $id)
    {
        $course = Course::findOrFail($id);
        $request->validate([
            'faculty_id' => 'required|exists:faculties,id',
            'lecturer_id' => 'nullable|exists:lecturers,id',
            'name' => 'required|string',
            'code' => 'nullable|string',
            'semester' => 'required|integer',
            'sks' => 'required|integer'
        ]);
        $course->update($request->all());
        $course->load('lecturer.user');
        $courseArray = $course->toArray();
        $courseArray['lecturer_name'] = $course->lecturer ? ($course->lecturer->user->name ?? 'Unknown') : null;
        return response()->json(['success' => true, 'message' => 'Mata Kuliah berhasil diperbarui.', 'data' => $courseArray]);
    }

    public function destroyCourse($id)
    {
        Course::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Mata Kuliah berhasil dihapus.']);
    }

    /**
     * Get all academic years.
     */
    public function academicYears()
    {
        $years = AcademicYear::orderBy('name', 'desc')->get();
        return response()->json([
            'success' => true,
            'data' => $years
        ]);
    }

    public function activeAcademicYear()
    {
        $year = AcademicYear::where('is_active', true)->first();
        return response()->json([
            'success' => true,
            'data' => $year
        ]);
    }

    public function storeAcademicYear(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'term' => 'required|in:Ganjil,Genap',
            'is_active' => 'boolean'
        ]);
        
        if ($request->is_active) {
            AcademicYear::where('is_active', true)->update(['is_active' => false]);
        }
        
        $year = AcademicYear::create($request->all());
        return response()->json(['success' => true, 'message' => 'Tahun Akademik berhasil ditambahkan.', 'data' => $year]);
    }

    public function updateAcademicYear(Request $request, $id)
    {
        $year = AcademicYear::findOrFail($id);
        $request->validate([
            'name' => 'required|string',
            'term' => 'required|in:Ganjil,Genap',
            'is_active' => 'boolean'
        ]);

        if ($request->is_active && !$year->is_active) {
            AcademicYear::where('is_active', true)->update(['is_active' => false]);
        }

        $year->update($request->all());
        return response()->json(['success' => true, 'message' => 'Tahun Akademik berhasil diperbarui.', 'data' => $year]);
    }

    public function destroyAcademicYear($id)
    {
        AcademicYear::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Tahun Akademik berhasil dihapus.']);
    }
}
