<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Room;
use App\Models\Faculty;
use App\Models\Course;
use App\Models\AcademicYear;

class MasterDataController extends Controller
{
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
