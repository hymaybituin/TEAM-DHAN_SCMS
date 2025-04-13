<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRecord;
use Illuminate\Http\Request;

class MaintenanceRecordController extends Controller
{
    public function index()
    {
        return MaintenanceRecord::with(['inventoryEquipment', 'createdBy', 'updatedBy'])->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'inventory_equipment_id' => 'required|exists:inventory_equipments,id',
            'maintenance_date' => 'required|date',
            'description' => 'required',
            'performed_by' => 'required',
            'created_by' => 'required|exists:users,id',
            'updated_by' => 'required|exists:users,id',
        ]);

        return MaintenanceRecord::create($request->all());
    }

    public function show($id)
    {
        return MaintenanceRecord::with(['inventoryEquipment', 'createdBy', 'updatedBy'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $maintenanceRecord = MaintenanceRecord::findOrFail($id);

        $request->validate([
            'inventory_equipment_id' => 'sometimes|required|exists:inventory_equipments,id',
            'maintenance_date' => 'sometimes|required|date',
            'description' => 'sometimes|required',
            'performed_by' => 'sometimes|required',
            'created_by' => 'sometimes|required|exists:users,id',
            'updated_by' => 'sometimes|required|exists:users,id',
        ]);

        $maintenanceRecord->update($request->all());

        return $maintenanceRecord;
    }

    public function destroy($id)
    {
        $maintenanceRecord = MaintenanceRecord::findOrFail($id);
        $maintenanceRecord->delete();

        return response()->noContent();
    }
}
