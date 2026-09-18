<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttendanceRecord;
use App\Models\Lecturer;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    /**
     * Export attendance reports to CSV.
     */
    public function exportCsv(Request $request)
    {
        $month = $request->query('month', Carbon::now()->month);
        $year = $request->query('year', Carbon::now()->year);

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
        
        if ($totalDays === 0) $totalDays = 1;

        foreach ($lecturers as $lecturer) {
            $user = $lecturer->user;
            if (!$user) continue;

            $userRecords = $records->where('user_id', $user->id);
            
            $hadir = $userRecords->where('status', 'HADIR')->count();
            $terlambat = $userRecords->where('status', 'TERLAMBAT')->count();
            $alpha = $totalDays - ($hadir + $terlambat);
            if ($alpha < 0) $alpha = 0;

            $persentase = round((($hadir + $terlambat) / $totalDays) * 100);

            $reportData[] = [
                'NIDN/NIP' => $lecturer->nidn ?? $lecturer->nip ?? '-',
                'Nama Dosen' => $user->name,
                'Hadir' => $hadir,
                'Terlambat' => $terlambat,
                'Alpha' => $alpha,
                'Persentase (%)' => $persentase . '%'
            ];
        }

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=Laporan_Absensi_{$year}_{$month}.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = array('NIDN/NIP', 'Nama Dosen', 'Hadir', 'Terlambat', 'Alpha', 'Persentase (%)');

        $callback = function() use($reportData, $columns) {
            $file = fopen('php://output', 'w');
            // Add BOM for proper UTF-8 Excel support
            fputs($file, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
            
            fputcsv($file, $columns);

            foreach ($reportData as $row) {
                fputcsv($file, array(
                    $row['NIDN/NIP'],
                    $row['Nama Dosen'],
                    $row['Hadir'],
                    $row['Terlambat'],
                    $row['Alpha'],
                    $row['Persentase (%)']
                ));
            }

            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}
