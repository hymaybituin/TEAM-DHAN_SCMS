<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index()
    {
        return Warehouse::with(['creator', 'updater'])->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'address' => 'required',
            'created_by' => 'required|exists:users,id',
            'updated_by' => 'required|exists:users,id',
        ]);

        return Warehouse::create($request->all());
    }

    public function show($id)
    {
        return Warehouse::with(['creator', 'updater'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $warehouse = Warehouse::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required',
            'address' => 'sometimes|required',
            'created_by' => 'sometimes|required|exists:users,id',
            'updated_by' => 'sometimes|required|exists:users,id',
        ]);

        $warehouse->update($request->all());

        return $warehouse;
    }

    public function destroy($id)
    {
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->delete();

        return response()->noContent();
    }
}
