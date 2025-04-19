<?php

namespace App\Http\Controllers;

use App\Models\DemoUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DemoUnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(DemoUnit::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'incoming_id' => 'required|exists:incomings,id',
            'company_id' => 'required|exists:companies,id',
            'demo_start' => 'required|date',
            'demo_end' => 'nullable|date',
            'assigned_person' => 'required|string',
            'status' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $demoUnit = DemoUnit::create(array_merge(
            $request->all(),
            ['created_by' => Auth::id()]
        ));

        return response()->json($demoUnit, 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DemoUnit $demoUnit)
    {
        $request->validate([
            'incoming_id' => 'exists:incomings,id',
            'company_id' => 'exists:companies,id',
            'demo_start' => 'date',
            'demo_end' => 'nullable|date',
            'assigned_person' => 'string',
            'status' => 'string',
            'notes' => 'nullable|string',
        ]);

        $demoUnit->update(array_merge(
            $request->all(),
            ['updated_by' => Auth::id()]
        ));

        return response()->json($demoUnit);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DemoUnit $demoUnit)
    {
        $demoUnit->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}