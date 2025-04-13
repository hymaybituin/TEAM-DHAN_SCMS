<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        return Supplier::with(['creator', 'updater'])->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'contact_info' => 'required',
            'created_by' => 'required|exists:users,id',
            'updated_by' => 'required|exists:users,id',
        ]);

        return Supplier::create($request->all());
    }

    public function show($id)
    {
        return Supplier::with(['creator', 'updater'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $supplier = Supplier::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required',
            'contact_info' => 'sometimes|required',
            'created_by' => 'sometimes|required|exists:users,id',
            'updated_by' => 'sometimes|required|exists:users,id',
        ]);

        $supplier->update($request->all());

        return $supplier;
    }

    public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->delete();

        return response()->noContent();
    }
}
