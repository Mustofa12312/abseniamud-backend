<?php

namespace App\Services;

use App\Models\AttendanceEvent;
use App\Models\AttendanceRecord;
use App\Models\Location;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    protected LocationValidationService $locationValidationService;

    public function __construct(LocationValidationService $locationValidationService)
    {
        $this->locationValidationService = $locationValidationService;
    }

    // ─── Check-In ─────────────────────────────────────────────────────────────

    /**
     * Process a check-in request.
     *
     * @param  \App\Models\User  $user
     * @param  float  $lat
     * @param  float  $lon
     * @param  float  $accuracy  GPS accuracy in meters
     * @param  int|null  $locationId  Optional — if null, nearest active location is used
     * @return array{success: bool, message: string, data?: array}
     */
    public function processCheckIn($user, float $lat, float $lon, float $accuracy, ?int $locationId = null): array
    {
        $today = Carbon::today();

        // 1. Prevent duplicate check-in
        $existing = AttendanceRecord::where('user_id', $user->id)
            ->where('date', $today)
            ->whereNotNull('check_in_event_id')
            ->first();

        if ($existing) {
            return [
                'success' => false,
                'message' => 'Anda sudah melakukan check-in hari ini.',
            ];
        }

        // 2. Resolve location
        $location = $locationId
            ? Location::where('id', $locationId)->where('is_active', true)->first()
            : $this->findNearestActiveLocation($lat, $lon);

        if (!$location) {
            return [
                'success' => false,
                'message' => 'Tidak ada lokasi presensi aktif yang ditemukan.',
            ];
        }

        // 3. Validate GPS accuracy & radius (server-side, not frontend)
        $validation = $this->locationValidationService->isValidLocation(
            $lat, $lon, $accuracy,
            $location->latitude, $location->longitude,
            $location->radius, $location->max_accuracy
        );

        $now = Carbon::now();

        if (!$validation['is_valid']) {
            // Save the rejected event for audit trail
            AttendanceEvent::create([
                'user_id'     => $user->id,
                'location_id' => $location->id,
                'type'        => 'CHECK_IN',
                'latitude'    => $lat,
                'longitude'   => $lon,
                'accuracy'    => (int) $accuracy,
                'distance'    => $validation['distance'] ? (int) $validation['distance'] : null,
                'status'      => 'REJECTED',
                'reason'      => $validation['reason'],
                'event_time'  => $now,
            ]);

            return [
                'success' => false,
                'message' => $validation['reason'],
            ];
        }

        // 4. Determine attendance status (HADIR / TERLAMBAT)
        $lateToleranceMinutes = (int) $this->getSetting('late_tolerance_minutes', 15);
        $defaultCheckinTime   = $this->getSetting('default_checkin_time', '07:00');

        $scheduleStart = Carbon::parse($today->toDateString() . ' ' . $defaultCheckinTime);
        $deadline      = $scheduleStart->copy()->addMinutes($lateToleranceMinutes);
        $attendanceStatus = $now->lessThanOrEqualTo($deadline) ? 'HADIR' : 'TERLAMBAT';

        // 5. Save check-in event and attendance record inside a transaction
        DB::transaction(function () use (
            $user, $location, $lat, $lon, $accuracy, $validation, $now, $today, $attendanceStatus
        ) {
            $event = AttendanceEvent::create([
                'user_id'     => $user->id,
                'location_id' => $location->id,
                'type'        => 'CHECK_IN',
                'latitude'    => $lat,
                'longitude'   => $lon,
                'accuracy'    => (int) $accuracy,
                'distance'    => (int) $validation['distance'],
                'status'      => 'VALID',
                'reason'      => null,
                'event_time'  => $now,
            ]);

            AttendanceRecord::updateOrCreate(
                ['user_id' => $user->id, 'date' => $today],
                [
                    'check_in_event_id' => $event->id,
                    'status'            => $attendanceStatus,
                ]
            );
        });

        return [
            'success' => true,
            'message' => 'Check-in berhasil.',
            'data'    => [
                'check_in_at'  => $now->format('H:i:s'),
                'location'     => $location->name,
                'distance'     => (int) $validation['distance'],
                'status'       => $attendanceStatus,
            ],
        ];
    }

    // ─── Check-Out ────────────────────────────────────────────────────────────

    /**
     * Process a check-out request.
     *
     * @param  \App\Models\User  $user
     * @param  float  $lat
     * @param  float  $lon
     * @param  float  $accuracy
     * @return array{success: bool, message: string, data?: array}
     */
    public function processCheckOut($user, float $lat, float $lon, float $accuracy): array
    {
        $today = Carbon::today();

        // 1. User must have checked in today
        $record = AttendanceRecord::where('user_id', $user->id)
            ->where('date', $today)
            ->whereNotNull('check_in_event_id')
            ->first();

        if (!$record) {
            return [
                'success' => false,
                'message' => 'Anda belum melakukan check-in hari ini.',
            ];
        }

        // 2. Prevent duplicate check-out
        if ($record->check_out_event_id) {
            return [
                'success' => false,
                'message' => 'Anda sudah melakukan check-out hari ini.',
            ];
        }

        // 3. Resolve nearest active location
        $location = $this->findNearestActiveLocation($lat, $lon);

        if (!$location) {
            return [
                'success' => false,
                'message' => 'Tidak ada lokasi presensi aktif yang ditemukan.',
            ];
        }

        // 4. Validate GPS accuracy & radius
        $validation = $this->locationValidationService->isValidLocation(
            $lat, $lon, $accuracy,
            $location->latitude, $location->longitude,
            $location->radius, $location->max_accuracy
        );

        $now = Carbon::now();

        if (!$validation['is_valid']) {
            AttendanceEvent::create([
                'user_id'     => $user->id,
                'location_id' => $location->id,
                'type'        => 'CHECK_OUT',
                'latitude'    => $lat,
                'longitude'   => $lon,
                'accuracy'    => (int) $accuracy,
                'distance'    => $validation['distance'] ? (int) $validation['distance'] : null,
                'status'      => 'REJECTED',
                'reason'      => $validation['reason'],
                'event_time'  => $now,
            ]);

            return [
                'success' => false,
                'message' => $validation['reason'],
            ];
        }

        // 5. Save check-out event and update record
        DB::transaction(function () use ($user, $location, $lat, $lon, $accuracy, $validation, $now, $record) {
            $event = AttendanceEvent::create([
                'user_id'     => $user->id,
                'location_id' => $location->id,
                'type'        => 'CHECK_OUT',
                'latitude'    => $lat,
                'longitude'   => $lon,
                'accuracy'    => (int) $accuracy,
                'distance'    => (int) $validation['distance'],
                'status'      => 'VALID',
                'reason'      => null,
                'event_time'  => $now,
            ]);

            $record->update([
                'check_out_event_id' => $event->id,
            ]);
        });

        return [
            'success' => true,
            'message' => 'Check-out berhasil.',
            'data'    => [
                'check_out_at' => $now->format('H:i:s'),
                'location'     => $location->name,
                'distance'     => (int) $validation['distance'],
            ],
        ];
    }

    // ─── Today Status ─────────────────────────────────────────────────────────

    /**
     * Get attendance status for a user today.
     *
     * @param  \App\Models\User  $user
     * @return array
     */
    public function getTodayStatus($user): array
    {
        $record = AttendanceRecord::with(['checkInEvent.location', 'checkOutEvent'])
            ->where('user_id', $user->id)
            ->where('date', Carbon::today())
            ->first();

        if (!$record) {
            return [
                'status'       => 'NOT_CHECKED_IN',
                'check_in_at'  => null,
                'check_out_at' => null,
                'location'     => null,
                'distance'     => null,
            ];
        }

        if ($record->check_out_event_id) {
            $status = 'CHECKED_OUT';
        } elseif ($record->check_in_event_id) {
            $status = 'CHECKED_IN';
        } else {
            $status = 'NOT_CHECKED_IN';
        }

        return [
            'status'           => $status,
            'attendance_status'=> $record->status, // HADIR / TERLAMBAT
            'check_in_at'      => $record->checkInEvent
                ? Carbon::parse($record->checkInEvent->event_time)->format('H:i:s')
                : null,
            'check_out_at'     => $record->checkOutEvent
                ? Carbon::parse($record->checkOutEvent->event_time)->format('H:i:s')
                : null,
            'location'         => $record->checkInEvent?->location?->name,
            'distance'         => $record->checkInEvent?->distance,
            'accuracy'         => $record->checkInEvent?->accuracy,
        ];
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────

    /**
     * Find the nearest active location to the given coordinates.
     */
    private function findNearestActiveLocation(float $lat, float $lon): ?Location
    {
        $locations = Location::active()->get();

        if ($locations->isEmpty()) {
            return null;
        }

        $nearest     = null;
        $minDistance = PHP_INT_MAX;

        foreach ($locations as $location) {
            $distance = $this->locationValidationService->calculateDistance(
                $lat, $lon,
                $location->latitude,
                $location->longitude
            );

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearest     = $location;
            }
        }

        return $nearest;
    }

    /**
     * Get a setting value by key with a fallback default.
     */
    private function getSetting(string $key, mixed $default = null): mixed
    {
        $setting = \App\Models\Setting::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }
}
