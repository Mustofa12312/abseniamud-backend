<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Lecturer;
use App\Http\Requests\StoreLecturerRequest;
use App\Http\Requests\UpdateLecturerRequest;
use App\Http\Resources\LecturerResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;
use App\Models\Role;

class LecturerController extends Controller
{
    /**
     * Get all lecturers data.
     */
    public function index()
    {
        $lecturers = Lecturer::with('user')->get();

        return response()->json([
            'success' => true,
            'data' => LecturerResource::collection($lecturers)
        ]);
    }

    public function store(StoreLecturerRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
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

    public function update(UpdateLecturerRequest $request, $id)
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
                $user->update(['password' => Hash::make($request->password)]);
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

    public function destroy($id)
    {
        $lecturer = Lecturer::findOrFail($id);
        if ($lecturer->user) {
            $lecturer->user->delete();
        }
        // Lecturer will be deleted due to cascade
        return response()->json(['success' => true, 'message' => 'Dosen berhasil dihapus.']);
    }

    /**
     * Export all lecturers to CSV.
     */
    public function exportCsv()
    {
        $lecturers = Lecturer::with('user')->get();
        $csvFileName = 'master_dosen_' . date('Y_m_d_H_i_s') . '.csv';
        
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$csvFileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use($lecturers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Nama Lengkap', 'Email', 'NIDN', 'NIP', 'Telepon', 'Alamat']);

            foreach ($lecturers as $lecturer) {
                fputcsv($file, [
                    $lecturer->user ? $lecturer->user->name : '',
                    $lecturer->user ? $lecturer->user->email : '',
                    $lecturer->nidn,
                    $lecturer->nip,
                    $lecturer->phone,
                    $lecturer->address
                ]);
            }
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Import lecturers from CSV.
     */
    public function importCsv(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:2048'
        ]);

        $file = $request->file('file');
        
        // Open file
        if (($handle = fopen($file->getRealPath(), 'r')) !== FALSE) {
            $header = fgetcsv($handle, 1000, ',');
            // Assume header is: Nama Lengkap, Email, NIDN, NIP, Telepon, Alamat
            
            DB::beginTransaction();
            try {
                $role = Role::where('name', 'dosen')->first();
                $roleId = $role ? $role->id : 3;
                $importedCount = 0;

                while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                    // Skip if data is incomplete
                    if (count($data) < 2 || empty($data[0]) || empty($data[1])) {
                        continue;
                    }

                    $name = trim($data[0]);
                    $email = trim($data[1]);
                    $nidn = isset($data[2]) ? trim($data[2]) : null;
                    $nip = isset($data[3]) ? trim($data[3]) : null;
                    $phone = isset($data[4]) ? trim($data[4]) : null;
                    $address = isset($data[5]) ? trim($data[5]) : null;

                    // Check if email already exists
                    $user = User::where('email', $email)->first();
                    if (!$user) {
                        $user = User::create([
                            'name' => $name,
                            'email' => $email,
                            'password' => Hash::make('password'),
                            'role_id' => $roleId
                        ]);

                        Lecturer::create([
                            'user_id' => $user->id,
                            'nidn' => $nidn,
                            'nip' => $nip,
                            'phone' => $phone,
                            'address' => $address,
                        ]);
                        $importedCount++;
                    }
                }
                fclose($handle);
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => "Berhasil mengimpor $importedCount dosen baru."
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                fclose($handle);
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengimpor file: ' . $e->getMessage()
                ], 500);
            }
        }

        return response()->json(['success' => false, 'message' => 'File tidak valid.'], 400);
    }
}
