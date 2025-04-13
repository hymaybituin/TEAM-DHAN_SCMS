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
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'order_date' => 'required|date',
            'total_amount' => 'required|numeric',
            'status_id' => 'required|exists:statuses,id',
            'created_by' => 'required|exists:users,id',
            'updated_by' => 'required|exists:users,id',
        ]);

        return PurchaseOrder::create($request->all());
    }

    public function show($id)
    {
        return PurchaseOrder::with(['supplier', 'status', 'creator', 'updater'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);

        $request->validate([
            'supplier_id' => 'sometimes|required|exists:suppliers,id',
            'order_date' => 'sometimes|required|date',
            'total_amount' => 'sometimes|required|numeric',
            'status_id' => 'sometimes|required|exists:statuses,id',
            'created_by' => 'sometimes|required|exists:users,id',
            'updated_by' => 'sometimes|required|exists:users,id',
        ]);

        $purchaseOrder->update($request->all());

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
                'order_date' => $request->order_date,
                'status_id' => $this->getStatusId('Pending Approval'),
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

            if ($request->status == 'Received') {
                $purchaseOrderItems = PurchaseOrderItem::where('purchase_order_id', $id)->get();
                Log::info('Adding items to inventory for Purchase Order ID: ' . $id); // Log when adding items to inventory
                $this->addItemsToInventory($purchaseOrderItems, $userId);
            }
        });

        return response()->json(['message' => 'Purchase order status updated successfully']);
    }
    private function getStatusId($statusName)
    {
        return Status::where('name', $statusName)->first()->id;
    }

    private function addItemsToInventory($items, $userId)
    {
        foreach ($items as $item) {
            $product = Product::find($item->product_id);
            
            if (!$product) {
                Log::error('Product ID: ' . $item->product_id . ' not found.');
                continue;
            }
            
            $productGroup = $product->productGroup()->first();
            if (!$productGroup) {
                Log::error('Product Group not found for Product ID: ' . $item->product_id);
                continue;
            }
            
            $productType = $productGroup->productType()->first();
            if (!$productType) {
                Log::error('Product Type not found for Product Group ID: ' . $productGroup->id);
                continue;
            }
    
            if ($productType->name === 'consumable') {
                InventoryConsumable::create([
                    'product_id' => $item->product_id,
                    'purchase_order_item_id' => $item->id,
                    'quantity' => $item->quantity,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            } elseif ($productType->name === 'equipment') {
                for ($i = 0; $i < $item->quantity; $i++) {
                    InventoryEquipment::create([
                        'product_id' => $item->product_id,
                        'purchase_order_item_id' => $item->id,
                        'purchase_date' => now(),
                        'status_id' => $this->getStatusId('Pending'),
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);
                }
            }
        }
    }
    

}
