<?php

namespace App\Services;

class LocationValidationService
{
    /**
     * Calculate distance between two coordinates using Haversine formula.
     * Returns distance in meters.
     */
    public function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // Earth's radius in meters

        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        $latDelta = $lat2 - $lat1;
        $lonDelta = $lon2 - $lon1;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos($lat1) * cos($lat2) *
             sin($lonDelta / 2) * sin($lonDelta / 2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Validate if coordinates are within allowed location radius.
     */
    public function isValidLocation($userLat, $userLon, $accuracy, $locationLat, $locationLon, $radius, $maxAccuracy)
    {
        if ($accuracy > $maxAccuracy) {
            return [
                'is_valid' => false,
                'distance' => null,
                'reason' => 'Akurasi GPS terlalu rendah (' . $accuracy . 'm). Maksimal yang diizinkan adalah ' . $maxAccuracy . 'm.'
            ];
        }

        $distance = $this->calculateDistance($userLat, $userLon, $locationLat, $locationLon);

        if ($distance > $radius) {
            return [
                'is_valid' => false,
                'distance' => $distance,
                'reason' => 'Anda berada di luar area presensi. Jarak Anda: ' . round($distance) . 'm. Radius maksimal: ' . $radius . 'm.'
            ];
        }

        return [
            'is_valid' => true,
            'distance' => $distance,
            'reason' => 'Lokasi valid.'
        ];
    }
}
