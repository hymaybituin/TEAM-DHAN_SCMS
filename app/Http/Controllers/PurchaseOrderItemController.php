<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrderItem;
use Illuminate\Http\Request;

class PurchaseOrderItemController extends Controller
{
    public function index()
    {
        return PurchaseOrderItem::with(['purchaseOrder', 'product', 'creator', 'updater'])->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric',
            'unit_price' => 'required|numeric',
            'total_price' => 'required|numeric',
            'created_by' => 'required|exists:users,id',
            'updated_by' => 'required|exists:users,id',
        ]);

        return PurchaseOrderItem::create($request->all());
    }

    public function show($id)
    {
        return PurchaseOrderItem::with(['purchaseOrder', 'product', 'creator', 'updater'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $purchaseOrderItem = PurchaseOrderItem::findOrFail($id);

        $request->validate([
            'purchase_order_id' => 'sometimes|required|exists:purchase_orders,id',
            'product_id' => 'sometimes|required|exists:products,id',
            'quantity' => 'sometimes|required|numeric',
            'unit_price' => 'sometimes|required|numeric',
            'total_price' => 'sometimes|required|numeric',
            'created_by' => 'sometimes|required|exists:users,id',
            'updated_by' => 'sometimes|required|exists:users,id',
        ]);

        $purchaseOrderItem->update($request->all());

        return $purchaseOrderItem;
    }

    public function destroy($id)
    {
        $purchaseOrderItem = PurchaseOrderItem::findOrFail($id);
        $purchaseOrderItem->delete();

        return response()->noContent();
    }
}
