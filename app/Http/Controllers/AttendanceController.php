<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckInRequest;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use App\Models\AttendanceRecord;
use App\Models\AttendanceCorrection;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    protected AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    // ─── Today Status ─────────────────────────────────────────────────────────

    /**
     * Get real attendance status for today.
     */
    public function today(Request $request)
    {
        $data = $this->attendanceService->getTodayStatus($request->user());

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    // ─── Check-In ─────────────────────────────────────────────────────────────

    /**
     * Handle Check-in.
     */
    public function checkIn(CheckInRequest $request)
    {
        $result = $this->attendanceService->processCheckIn(
            $request->user(),
            (float) $request->latitude,
            (float) $request->longitude,
            (float) $request->accuracy,
            $request->location_id ? (int) $request->location_id : null,
            $request->ip(),
            $request->userAgent()
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data'    => $result['data'] ?? null,
        ]);
    }

    // ─── Check-Out ────────────────────────────────────────────────────────────

    /**
     * Handle Check-out.
     */
    public function checkOut(CheckInRequest $request)
    {
        $result = $this->attendanceService->processCheckOut(
            $request->user(),
            (float) $request->latitude,
            (float) $request->longitude,
            (float) $request->accuracy,
            $request->ip(),
            $request->userAgent()
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data'    => $result['data'] ?? null,
        ]);
    }

    // ─── History ──────────────────────────────────────────────────────────────

    /**
     * Get attendance history for the authenticated user.
     */
    public function history(Request $request)
    {
        $month = $request->query('month');
        $year  = $request->query('year');

        $query = AttendanceRecord::with(['checkInEvent.location', 'checkOutEvent'])
            ->where('user_id', $request->user()->id)
            ->orderBy('date', 'desc');

        if ($month && $year) {
            $query->whereMonth('date', $month)->whereYear('date', $year);
        }
        
        $perPage = $request->input('per_page', 10);
        $paginator = $query->paginate($perPage);

        $records = $paginator->map(function ($record) {
            return [
                'id'       => $record->id,
                'date'     => Carbon::parse($record->date)->translatedFormat('d M Y'),
                'raw_date' => $record->date->toDateString(),
                'checkIn'  => $record->checkInEvent
                    ? Carbon::parse($record->checkInEvent->event_time)->format('H:i')
                    : '-',
                'checkOut' => $record->checkOutEvent
                    ? Carbon::parse($record->checkOutEvent->event_time)->format('H:i')
                    : '-',
                'location' => $record->checkInEvent?->location?->name ?? '-',
                'distance' => $record->checkInEvent?->distance,
                'status'   => $record->status,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $records,
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total()
            ]
        ]);
    }

    // ─── Summary ──────────────────────────────────────────────────────────────

    /**
     * Get monthly attendance summary for the authenticated user.
     */
    public function summary(Request $request)
    {
        $month = (int) $request->query('month', Carbon::now()->month);
        $year  = (int) $request->query('year', Carbon::now()->year);

        $records = AttendanceRecord::where('user_id', $request->user()->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();

        $hadir         = $records->where('status', 'HADIR')->count();
        $terlambat     = $records->where('status', 'TERLAMBAT')->count();
        $tidakHadir    = $records->where('status', 'TIDAK_HADIR')->count();
        $belumCheckout = $records->whereNotNull('check_in_event_id')
                                 ->whereNull('check_out_event_id')
                                 ->count();

        return response()->json([
            'success' => true,
            'data'    => [
                'period'         => Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y'),
                'hadir'          => $hadir,
                'terlambat'      => $terlambat,
                'tidak_hadir'    => $tidakHadir,
                'belum_checkout' => $belumCheckout,
                'total'          => $records->count(),
            ],
        ]);
    }

    // ─── Corrections ──────────────────────────────────────────────────────────

    /**
     * Submit attendance correction.
     */
    public function storeCorrection(Request $request)
    {
        $validated = $request->validate([
            'date'   => 'required|date|before_or_equal:today',
            'type'   => 'required|string|max:100',
            'reason' => 'required|string|max:1000',
        ]);

        // Check for duplicate pending correction on the same date
        $duplicate = AttendanceCorrection::where('user_id', $request->user()->id)
            ->where('date', $validated['date'])
            ->where('status', 'PENDING')
            ->exists();

        if ($duplicate) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah mengajukan koreksi untuk tanggal ini dan masih menunggu persetujuan.',
            ], 422);
        }

        $correction = AttendanceCorrection::create([
            'user_id' => $request->user()->id,
            'date'    => $validated['date'],
            'type'    => $validated['type'],
            'reason'  => $validated['reason'],
            'status'  => 'PENDING',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan koreksi presensi berhasil dikirim.',
            'data'    => $correction,
        ]);
    }

    /**
     * Get corrections for the authenticated user.
     */
    public function getCorrections(Request $request)
    {
        $query = AttendanceCorrection::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc');

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $perPage = $request->input('per_page', 10);
        $paginator = $query->paginate($perPage);

        $corrections = $paginator->map(function ($c) {
            return [
                'id'         => $c->id,
                'date'       => Carbon::parse($c->date)->translatedFormat('d F Y'),
                'raw_date'   => $c->date,
                'type'       => $c->type,
                'reason'     => $c->reason,
                'status'     => $c->status,
                'created_at' => $c->created_at->format('d/m/Y H:i'),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $corrections,
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total()
            ]
        ]);
    }
}
