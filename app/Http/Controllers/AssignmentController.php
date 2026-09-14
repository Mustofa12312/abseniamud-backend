<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Position;
use App\Models\Assignment;
use App\Http\Requests\StorePositionRequest;
use App\Http\Requests\UpdatePositionRequest;
use App\Http\Requests\StoreAssignmentRequest;
use App\Http\Requests\UpdateAssignmentRequest;

class AssignmentController extends Controller
{
    // --- POSITIONS ---

    public function positions()
    {
        $positions = Position::orderBy('name')->get();
        return response()->json(['success' => true, 'data' => $positions]);
    }

    public function storePosition(StorePositionRequest $request)
    {
        $validated = $request->validated();
        $position = Position::create($validated);
        return response()->json(['success' => true, 'message' => 'Jabatan berhasil ditambahkan', 'data' => $position]);
    }

    public function updatePosition(UpdatePositionRequest $request, $id)
    {
        $position = Position::findOrFail($id);
        $validated = $request->validated();
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

    public function storeAssignment(StoreAssignmentRequest $request)
    {
        $validated = $request->validated();

        if (!empty($validated['is_primary']) && $validated['is_primary']) {
            Assignment::where('lecturer_id', $validated['lecturer_id'])->update(['is_primary' => false]);
        }

        $assignment = Assignment::create($validated);
        $assignment->load(['lecturer.user', 'position']);

        return response()->json(['success' => true, 'message' => 'Penugasan berhasil ditambahkan', 'data' => $assignment]);
    }

    public function updateAssignment(UpdateAssignmentRequest $request, $id)
    {
        $assignment = Assignment::findOrFail($id);
        $validated = $request->validated();

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
