<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Position;
use App\Models\Assignment;

class AssignmentController extends Controller
{
    // --- POSITIONS ---

    public function positions()
    {
        $positions = Position::orderBy('name')->get();
        return response()->json(['success' => true, 'data' => $positions]);
    }

    public function storePosition(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:positions,name'
        ]);
        $position = Position::create($validated);
        return response()->json(['success' => true, 'message' => 'Jabatan berhasil ditambahkan', 'data' => $position]);
    }

    public function updatePosition(Request $request, $id)
    {
        $position = Position::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|unique:positions,name,' . $id
        ]);
        $position->update($validated);
        return response()->json(['success' => true, 'message' => 'Jabatan berhasil diperbarui', 'data' => $position]);
    }

    public function destroyPosition($id)
    {
        Position::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Jabatan berhasil dihapus']);
    }

    // --- ASSIGNMENTS ---

    public function assignments(Request $request)
    {
        $query = Assignment::with(['lecturer.user', 'position']);
        
        if ($request->has('lecturer_id')) {
            $query->where('lecturer_id', $request->lecturer_id);
        }

        $assignments = $query->get()->map(function($a) {
            return [
                'id' => $a->id,
                'lecturer_id' => $a->lecturer_id,
                'lecturer_name' => $a->lecturer->user->name ?? 'Unknown',
                'position_id' => $a->position_id,
                'position_name' => $a->position->name ?? 'Unknown',
                'is_primary' => $a->is_primary
            ];
        });

        return response()->json(['success' => true, 'data' => $assignments]);
    }

    public function storeAssignment(Request $request)
    {
        $validated = $request->validate([
            'lecturer_id' => 'required|exists:lecturers,id',
            'position_id' => 'required|exists:positions,id',
            'is_primary' => 'boolean'
        ]);

        if (!empty($validated['is_primary']) && $validated['is_primary']) {
            Assignment::where('lecturer_id', $validated['lecturer_id'])->update(['is_primary' => false]);
        }

        $assignment = Assignment::create($validated);
        $assignment->load(['lecturer.user', 'position']);

        return response()->json(['success' => true, 'message' => 'Penugasan berhasil ditambahkan', 'data' => $assignment]);
    }

    public function updateAssignment(Request $request, $id)
    {
        $assignment = Assignment::findOrFail($id);
        $validated = $request->validate([
            'lecturer_id' => 'required|exists:lecturers,id',
            'position_id' => 'required|exists:positions,id',
            'is_primary' => 'boolean'
        ]);

        if (!empty($validated['is_primary']) && $validated['is_primary']) {
            Assignment::where('lecturer_id', $validated['lecturer_id'])
                ->where('id', '!=', $id)
                ->update(['is_primary' => false]);
        }

        $assignment->update($validated);
        $assignment->load(['lecturer.user', 'position']);

        return response()->json(['success' => true, 'message' => 'Penugasan berhasil diperbarui', 'data' => $assignment]);
    }

    public function destroyAssignment($id)
    {
        Assignment::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Penugasan berhasil dihapus']);
    }
}
