<?php

namespace App\Http\Controllers;

use App\Models\CalibrationRecord;
use Illuminate\Http\Request;

class CalibrationRecordController extends Controller
{
    public function index()
    {
        return CalibrationRecord::with(['inventoryEquipment', 'calibrationStatus', 'createdBy', 'updatedBy'])->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'incoming_stock_id' => 'required|exists:incoming_stocks,id',
            'calibration_date' => 'required|date',
            'calibrated_by' => 'required',
            'calibration_status_id' => 'required|exists:statuses,id',
            'calibration_notes' => 'required',
        ]);

        $validatedData['created_by'] = auth()->id();
        $validatedData['updated_by'] = auth()->id();

        return CalibrationRecord::create($request->all());
    }

    public function show($id)
    {
        return CalibrationRecord::with(['inventoryEquipment', 'calibrationStatus', 'createdBy', 'updatedBy'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $calibrationRecord = CalibrationRecord::findOrFail($id);

        $request->validate([
            'incoming_stock_id' => 'required|exists:incoming_stocks,id',
            'calibration_date' => 'required|date',
            'calibrated_by' => 'required',
            'calibration_status_id' => 'required|exists:statuses,id',
            'calibration_notes' => 'required',
        ]);

        $validatedData['created_by'] = auth()->id();
        $validatedData['updated_by'] = auth()->id();

        $calibrationRecord->update($request->all());

        return $calibrationRecord;
    }

    public function destroy($id)
    {
        $calibrationRecord = CalibrationRecord::findOrFail($id);
        $calibrationRecord->delete();

        return response()->noContent();
    }
}
