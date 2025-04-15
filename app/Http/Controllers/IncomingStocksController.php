<?php

namespace App\Http\Controllers;

use App\Models\IncomingStock;
use Illuminate\Http\Request;

class IncomingStocksController extends Controller
{
    /**
     * Update serial_number, lot_number, and expiration_date for stocks by purchase_order_item_id and product_id.
     */
    public function updateIncomingStock(Request $request)
    {
        // ✅ Validate Request
        $validatedData = $request->validate([
            'purchase_order_item_id' => 'required|exists:incoming_stocks,purchase_order_item_id',
            'product_id' => 'required|exists:incoming_stocks,product_id',
            'serial_number' => 'nullable|string|max:255',
            'lot_number' => 'nullable|string|max:255',
            'expiration_date' => 'nullable|date',
        ]);

        // ✅ Find Incoming Stocks Matching Criteria
        $incomingStocks = IncomingStock::where('purchase_order_item_id', $validatedData['purchase_order_item_id'])
            ->where('product_id', $validatedData['product_id'])
            ->get();

        if ($incomingStocks->isEmpty()) {
            return response()->json(['message' => 'No matching incoming stocks found'], 404);
        }

        // ✅ Update Fields for Each Matching Incoming Stock
        foreach ($incomingStocks as $stock) {
            $stock->update([
                'serial_number' => $validatedData['serial_number'] ?? $stock->serial_number,
                'lot_number' => $validatedData['lot_number'] ?? $stock->lot_number,
                'expiration_date' => $validatedData['expiration_date'] ?? $stock->expiration_date,
                'updated_by_user_id' => auth()->id(), // Track updater
            ]);
        }

        return response()->json([
            'message' => 'Incoming stocks updated successfully',
            'updated_stocks' => $incomingStocks->fresh(),
        ]);
    }
}