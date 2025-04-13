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
            'inventory_equipment_id' => 'required|exists:inventory_equipments,id',
            'calibration_date' => 'required|date',
            'next_calibration_due' => 'required|date',
            'calibrated_by' => 'required',
            'calibration_status_id' => 'required|exists:statuses,id',
            'calibration_notes' => 'required',
            'created_by' => 'required|exists:users,id',
            'updated_by' => 'required|exists:users,id',
        ]);

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
            'inventory_equipment_id' => 'sometimes|required|exists:inventory_equipments,id',
            'calibration_date' => 'sometimes|required|date',
            'next_calibration_due' => 'sometimes|required|date',
            'calibrated_by' => 'sometimes|required',
            'calibration_status_id' => 'sometimes|required|exists:statuses,id',
            'calibration_notes' => 'sometimes|required',
            'created_by' => 'sometimes|required|exists:users,id',
            'updated_by' => 'sometimes|required|exists:users,id',
        ]);

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
