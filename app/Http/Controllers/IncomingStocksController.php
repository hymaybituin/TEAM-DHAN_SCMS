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
            'barcodes' => 'required|array|min:1', // Ensure an array with at least one barcode
            'serial_number' => 'nullable|string|max:255',
            'lot_number' => 'nullable|string|max:255',
            'expiration_date' => 'nullable|date',
        ]);
    
        // ✅ Find Incoming Stocks Matching Barcodes
        $incomingStocks = IncomingStock::whereIn('barcode', $validatedData['barcodes'])->get();
    
        if ($incomingStocks->isEmpty()) {
            return response()->json(['message' => 'No matching incoming stocks found for the provided barcodes'], 404);
        }
    
        // ✅ Bulk Update Using `updateByBarcodes()` Helper Method in Model
        IncomingStock::whereIn('barcode', $validatedData['barcodes'])->update([
            'serial_number' => $validatedData['serial_number'] ?? null,
            'lot_number' => $validatedData['lot_number'] ?? null,
            'expiration_date' => $validatedData['expiration_date'] ?? null,
            'updated_by_user_id' => auth()->id(), // Track updater
        ]);
    
        return response()->json([
            'message' => 'Incoming stocks updated successfully',
            'updated_stocks' => IncomingStock::whereIn('barcode', $validatedData['barcodes'])->get(),
        ]);
    }
}