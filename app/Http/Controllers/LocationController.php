<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index()
    {
        return Location::with(['creator', 'updater'])->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'required',
            'created_by' => 'required|exists:users,id',
            'updated_by' => 'required|exists:users,id',
        ]);

        return Location::create($request->all());
    }

    public function show($id)
    {
        return Location::with(['creator', 'updater'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $location = Location::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required',
            'description' => 'sometimes|required',
            'created_by' => 'sometimes|required|exists:users,id',
            'updated_by' => 'sometimes|required|exists:users,id',
        ]);

        $location->update($request->all());

        return $location;
    }

    public function destroy($id)
    {
        $location = Location::findOrFail($id);
        $location->delete();

        return response()->noContent();
    }
}
