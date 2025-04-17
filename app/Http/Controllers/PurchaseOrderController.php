<?php

namespace App\Http\Controllers;

use App\Models\Status;
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use App\Models\Product;
use App\Models\PurchaseOrderItem;
use App\Models\InventoryEquipment;
use Illuminate\Support\Facades\DB;
use App\Models\InventoryConsumable;
use App\Models\PurchaseOrderStatus;
use Illuminate\Support\Facades\Log;
use App\Models\SupplierProductPrice;
use Illuminate\Support\Facades\Auth;
use App\Models\SupplierProductPrices;

class PurchaseOrderController extends Controller
{

    public function getPurchaseOrderDetails($purchaseOrderId = null)
    {
        // Check if a specific purchaseOrderId is provided
        if ($purchaseOrderId) {
            // Fetch details for the specified Purchase Order
            $purchaseOrder = PurchaseOrder::with([
                'status',
                'items.product',
                'items.deliveries'
            ])->findOrFail($purchaseOrderId);

            // Calculate remaining quantity for each item
            $purchaseOrder->items->each(function ($item) {
                $totalDelivered = $item->deliveries->sum('delivered_quantity');
                $item->remaining_quantity = $item->quantity - $totalDelivered;
            });

            return response()->json(['purchase_order' => $purchaseOrder]);
        } else {
            // Fetch details for all Purchase Orders
            $purchaseOrders = PurchaseOrder::with([
                'status',
                'items.product',
                'items.deliveries'
            ])->get();

            // Calculate remaining quantity for each item in all purchase orders
            $purchaseOrders->each(function ($purchaseOrder) {
                $purchaseOrder->items->each(function ($item) {
                    $totalDelivered = $item->deliveries->sum('delivered_quantity');
                    $item->remaining_quantity = $item->quantity - $totalDelivered;
                });
            });

            return response()->json(['purchase_orders' => $purchaseOrders]);
        }
    }

    
    public function index()
    {
        return PurchaseOrder::with(['supplier', 'status', 'creator', 'updater'])->get();
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'order_date' => 'required|date',
            'total_amount' => 'required|numeric',
            'status_id' => 'required|exists:statuses,id',
        ]);
    
        // Set created_by and updated_by from authenticated user
        $validatedData['created_by'] = auth()->id();
        $validatedData['updated_by'] = auth()->id();
    
        return PurchaseOrder::create($validatedData);
    }
    public function show($id)
    {
        return PurchaseOrder::with(['supplier', 'status', 'creator', 'updater'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);
    
        $validatedData = $request->validate([
            'supplier_id' => 'sometimes|required|exists:suppliers,id',
            'order_date' => 'sometimes|required|date',
            'total_amount' => 'sometimes|required|numeric',
            'status_id' => 'sometimes|required|exists:statuses,id',
        ]);
    
        // Set updated_by from authenticated user
        $validatedData['updated_by'] = auth()->id();
    
        $purchaseOrder->update($validatedData);
    
        return $purchaseOrder;
    }


    public function destroy($id)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);
        $purchaseOrder->delete();

        return response()->noContent();
    }



    //Start of free functions

    public function createPurchaseOrder(Request $request)
    {
        $userId = Auth::id(); // Get the ID of the authenticated user
    
        // Check if the authenticated user ID is null
        if (is_null($userId)) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }
    
        DB::transaction(function () use ($request, $userId) {
            $totalAmount = 0;
    
            $purchaseOrder = PurchaseOrder::create([
                'supplier_id' => $request->supplier_id,
                'order_date' => now(),
                'status_id' => $this->getStatusId('Pending'),
                'total_amount' => 0, // Set initial total_amount to 0
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
    
            foreach ($request->items as $item) {
                $supplierProductPrice = SupplierProductPrice::where('supplier_id', $request->supplier_id)
                    ->where('product_id', $item['product_id'])
                    ->first();
    
                if ($supplierProductPrice) {
                    $unitPrice = $supplierProductPrice->price;
                    $totalPrice = $item['quantity'] * $unitPrice;
                    $totalAmount += $totalPrice;
    
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $purchaseOrder->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);
                }
            }
    
            $purchaseOrder->total_amount = $totalAmount;
            $purchaseOrder->save();
    
            PurchaseOrderStatus::create([
                'purchase_order_id' => $purchaseOrder->id,
                'status_id' => $this->getStatusId('Pending Approval'),
                'status_date' => now(),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        });
    
        return response()->json(['message' => 'Purchase order created successfully'], 201);
    }

    public function updatePurchaseOrderStatus(Request $request, $id)
    {
        $userId = Auth::id(); // Get the ID of the authenticated user

        DB::transaction(function () use ($request, $id, $userId) {
            $purchaseOrder = PurchaseOrder::find($id);
            $statusId = $this->getStatusId($request->status);

            $purchaseOrder->status_id = $statusId;
            $purchaseOrder->updated_by = $userId;
            $purchaseOrder->save();

            PurchaseOrderStatus::create([
                'purchase_order_id' => $purchaseOrder->id,
                'status_id' => $statusId,
                'status_date' => now(),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

        
        });

        return response()->json(['message' => 'Purchase order status updated successfully']);
    }
    private function getStatusId($statusName)
    {
        return Status::where('name', $statusName)->first()->id;
    }


}
