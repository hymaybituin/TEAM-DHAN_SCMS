<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\IncomingStock;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\Auth;
use App\Models\PurchaseOrderItemDelivery;


class PurchaseOrderItemDeliveryController extends Controller
{
    public function index()
    {
        $deliveries = PurchaseOrderItemDelivery::with('purchaseOrderItem')->get();
        return response()->json($deliveries);
    }

    

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'delivered_quantity' => 'integer|min:1',
            'delivery_date' => 'date',
            'updated_by' => 'exists:users,id',
        ]);

        $delivery = PurchaseOrderItemDelivery::findOrFail($id);
        $delivery->update($validated);

        return response()->json($delivery);
    }

    public function destroy($id)
    {
        $delivery = PurchaseOrderItemDelivery::findOrFail($id);
        $delivery->delete();

        return response()->json(['message' => 'Delivery deleted successfully.']);
    }


    public function store(Request $request)
    {
        // Get the authenticated user's ID
        $userId = Auth::id();

        // Validate the request input
        $validated = $request->validate([
            'purchase_order_item_id' => 'required|exists:purchase_order_items,id',
            'delivered_quantity' => ['required', 'integer', 'min:1'],
            'delivery_date' => 'required|date',
            'lot_number' => 'nullable|string',
            'expiration_date' => 'nullable|date',
            'serial_number' => 'nullable|string',
        ]);

        // Fetch the **purchase order item** to get the associated **product**
        $purchaseOrderItem = PurchaseOrderItem::with('product')->find($validated['purchase_order_item_id']);
        if (!$purchaseOrderItem || !$purchaseOrderItem->product) {
            return response()->json(['message' => 'Invalid purchase order item or missing associated product.'], 422);
        }

        // Calculate total delivered quantity
        $totalDelivered = PurchaseOrderItemDelivery::where('purchase_order_item_id', $validated['purchase_order_item_id'])
            ->sum('delivered_quantity');

        $remainingQuantity = $purchaseOrderItem->quantity - $totalDelivered;

        // **Check if the new delivery exceeds the remaining quantity**
        if ($validated['delivered_quantity'] > $remainingQuantity) {
            return response()->json([
                'message' => "The delivered quantity ({$validated['delivered_quantity']}) exceeds the remaining quantity ({$remainingQuantity})."
            ], 422);
        }

        // Get product details
        $product = $purchaseOrderItem->product;
        $productId = $product->id;
        $currentYear = now()->format('Y'); // Get current year dynamically

        // Convert expiration_date to proper format before storing
        if (!empty($validated['expiration_date'])) {
            try {
                $validated['expiration_date'] = Carbon::parse($validated['expiration_date'])->format('Y-m-d');
            } catch (\Exception $e) {
                return response()->json(['message' => 'Invalid expiration date format. Use DD-MM-YYYY.'], 422);
            }
        }

        // First, save data to **purchase_order_item_deliveries**
        $deliveryRecord = PurchaseOrderItemDelivery::create([
            'purchase_order_item_id' => $validated['purchase_order_item_id'],
            'delivered_quantity' => $validated['delivered_quantity'],
            'delivery_date' => $validated['delivery_date'],
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $barcodes = [];

        // Store each item separately with a **unique barcode**
        for ($i = 0; $i < $validated['delivered_quantity']; $i++) {
            $incomingStock = IncomingStock::create([
                'purchase_order_item_id' => $validated['purchase_order_item_id'],
                'serial_number' => $validated['serial_number'] ?? null,
                'lot_number' => $validated['lot_number'] ?? null,
                'expiration_date' => $validated['expiration_date'] ?? null, // Already formatted
                'product_id' => $productId,
                'quantity' => 1, // Each item gets its own record
                'created_by_user_id' => $userId,
                'updated_by_user_id' => $userId,
            ]);

            // Generate barcode in the format: YEAR + 6 random digits
            $barcode = "{$currentYear}" . str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $incomingStock->barcode = $barcode;
            $incomingStock->save();

            // Collect saved barcodes
            $barcodes[] = $barcode;
        }

        // Return only the saved barcodes
        return response()->json([
            'barcodes' => $barcodes,
        ], 201);
    }

}
