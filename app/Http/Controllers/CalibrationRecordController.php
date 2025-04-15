<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IncomingStock;
use App\Models\CalibrationRecord;

class CalibrationRecordController extends Controller
{
    // Retrieve all calibration records with related entities
    public function index()
    {
        return CalibrationRecord::with(['inventoryEquipment', 'calibrationStatus', 'createdBy', 'updatedBy'])->get();
    }

    // Store a new calibration record using serial number
    public function store(Request $request)
    {
        $request->validate([
            'serial_number' => 'required|exists:incoming_stocks,serial_number', 
            'calibration_date' => 'required|date',
            'calibrated_by' => 'required',
            'calibration_status_id' => 'required|exists:statuses,id',
            'calibration_notes' => 'required',
        ]);

        $incomingStock = IncomingStock::where('serial_number', $request->serial_number)->first();

        if (!$incomingStock) {
            return response()->json(['error' => 'No stock found for the provided serial number'], 404);
        }

        $validatedData = $request->all();
        $validatedData['incoming_stock_id'] = $incomingStock->id;
        $validatedData['created_by'] = auth()->id();
        $validatedData['updated_by'] = auth()->id();

        return CalibrationRecord::create($validatedData);
    }

    public function show($serialNumber)
    {
        // Find calibration records by serial number
        $incomingStock = IncomingStock::where('serial_number', $serialNumber)->first();
    
        if (!$incomingStock) {
            return response()->json(['error' => 'No stock found for the provided serial number'], 404);
        }
    
        // Retrieve calibration records linked to the incoming stock
        return CalibrationRecord::with(['inventoryEquipment', 'calibrationStatus', 'createdBy', 'updatedBy'])
            ->where('incoming_stock_id', $incomingStock->id)
            ->get();
    }

    // Update an existing calibration record using serial number
    public function update(Request $request, $id)
    {
        $calibrationRecord = CalibrationRecord::findOrFail($id);

        $request->validate([
            'serial_number' => 'required|exists:incoming_stocks,serial_number', 
            'calibration_date' => 'required|date',
            'calibrated_by' => 'required',
            'calibration_status_id' => 'required|exists:statuses,id',
            'calibration_notes' => 'required',
        ]);

        $incomingStock = IncomingStock::where('serial_number', $request->serial_number)->first();

        if (!$incomingStock) {
            return response()->json(['error' => 'No stock found for the provided serial number'], 404);
        }

        $validatedData = $request->all();
        $validatedData['incoming_stock_id'] = $incomingStock->id;
        $validatedData['updated_by'] = auth()->id();

        $calibrationRecord->update($validatedData);

        return $calibrationRecord;
    }

    // Delete a specific calibration record by ID
    public function destroy($id)
    {
        $calibrationRecord = CalibrationRecord::findOrFail($id);
        $calibrationRecord->delete();

        return response()->noContent();
    }
}