<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Lecturer;
use App\Http\Requests\StoreLecturerRequest;
use App\Http\Requests\UpdateLecturerRequest;
use App\Http\Resources\LecturerResource;
use Illuminate\Support\Facades\Hash;

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
}
