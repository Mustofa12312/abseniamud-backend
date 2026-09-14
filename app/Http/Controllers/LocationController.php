<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Http\Requests\StoreLocationRequest;
use App\Http\Requests\UpdateLocationRequest;
use App\Http\Resources\LocationResource;

class LocationController extends Controller
{
    /**
     * Get locations.
     */
    public function index()
    {
        $locations = Location::all();

        return response()->json([
            'success' => true,
            'data' => LocationResource::collection($locations)
        ]);
    }

    public function store(StoreLocationRequest $request)
    {
        $validated = $request->validated();

        $location = Location::create($validated);
        return response()->json(['success' => true, 'message' => 'Lokasi berhasil ditambahkan.', 'data' => $location]);
    }

    public function update(UpdateLocationRequest $request, $id)
    {
        $location = Location::findOrFail($id);
        $validated = $request->validated();

        $location->update($validated);
        return response()->json(['success' => true, 'message' => 'Lokasi berhasil diperbarui.', 'data' => $location]);
    }

    public function destroy($id)
    {
        Location::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Lokasi berhasil dihapus.']);
    }
}
