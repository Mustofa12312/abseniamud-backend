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
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Get attendance status for today.
     */
    public function today(Request $request)
    {
        // Mocked response for now
        return response()->json([
            'success' => true,
            'data' => [
                'status' => 'NOT_CHECKED_IN',
                'check_in_at' => null,
                'check_out_at' => null,
                'location' => null
            ]
        ]);
    }

    /**
     * Handle Check-in.
     */
    public function checkIn(CheckInRequest $request)
    {
        $result = $this->attendanceService->processCheckIn(
            $request->user(),
            $request->latitude,
            $request->longitude,
            $request->accuracy,
            $request->location_id
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'data' => $result['data'] ?? null
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Check-in berhasil.',
            'data' => $result['data'] ?? null
        ]);
    }

    /**
     * Handle Check-out.
     */
    public function checkOut(CheckInRequest $request)
    {
        $result = $this->attendanceService->processCheckOut(
            $request->user(),
            $request->latitude,
            $request->longitude,
            $request->accuracy
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'data' => $result['data'] ?? null
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Check-out berhasil.',
            'data' => $result['data'] ?? null
        ]);
    }

    /**
     * Get attendance history for the authenticated user.
     */
    public function history(Request $request)
    {
        $records = AttendanceRecord::with(['checkInEvent', 'checkOutEvent'])
            ->where('user_id', $request->user()->id)
            ->orderBy('date', 'desc')
            ->take(30) // Last 30 days
            ->get()
            ->map(function ($record) {
                return [
                    'id' => $record->id,
                    'date' => Carbon::parse($record->date)->translatedFormat('d M Y'),
                    'checkIn' => $record->checkInEvent ? Carbon::parse($record->checkInEvent->event_time)->format('H:i') : '-',
                    'checkOut' => $record->checkOutEvent ? Carbon::parse($record->checkOutEvent->event_time)->format('H:i') : '-',
                    'status' => $record->status,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $records
        ]);
    }

    /**
     * Submit attendance correction.
     */
    public function storeCorrection(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'type' => 'required|string',
            'reason' => 'required|string'
        ]);

        $correction = AttendanceCorrection::create([
            'user_id' => $request->user()->id,
            'date' => $validated['date'],
            'type' => $validated['type'],
            'reason' => $validated['reason'],
            'status' => 'PENDING'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan koreksi presensi berhasil dikirim',
            'data' => $correction
        ]);
    }

    /**
     * Get user corrections.
     */
    public function getCorrections(Request $request)
    {
        $corrections = AttendanceCorrection::where('user_id', $request->user()->id)
                            ->orderBy('created_at', 'desc')
                            ->get()
                            ->map(function($c) {
                                return [
                                    'id' => $c->id,
                                    'date' => Carbon::parse($c->date)->translatedFormat('d F Y'),
                                    'type' => $c->type,
                                    'reason' => $c->reason,
                                    'status' => $c->status,
                                    'created_at' => $c->created_at->format('d/m/Y H:i')
                                ];
                            });

        return response()->json([
            'success' => true,
            'data' => $corrections
        ]);
    }
}
