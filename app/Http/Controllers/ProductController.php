<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Product;
use App\Models\ProductType;
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use App\Models\SupplierProduct;
use App\Models\PurchaseOrderItem;

class ProductController extends Controller
{
    /**
     * Display a listing of the products with their total quantity and status.
     *
     * @return \Illuminate\Http\Response
     */
    /*public function getProductsWithQuantityAndStatus()
    {
        $productTypes = ProductType::with(['productGroups.products.inventoryConsumables', 'productGroups.products.inventoryEquipment'])
            ->get()
            ->map(function ($productType) {
                $productGroups = $productType->productGroups->map(function ($productGroup) {
                    $products = $productGroup->products->map(function ($product) {
                        $consumableQuantity = 0;
                        $equipmentQuantity = 0;

                        if ($product->productGroup->productType->name == 'consumable') {
                            $consumableQuantity = $product->inventoryConsumables
                                ->where('expiry_date', '>', now())
                                ->sum('quantity');
                        }

                        if ($product->productGroup->productType->name === 'equipment') {
                            $statusesToCheck = ['In Stock', 'Return Demo (working)']; // Add more statuses as needed
                            $equipmentQuantity = $product->inventoryEquipment
                                ->filter(function ($equipment) use ($statusesToCheck) {
                                    return in_array($equipment->status->name, $statusesToCheck, true);
                                })
                                ->count();
                        }

                        $availableQuantity = $consumableQuantity + $equipmentQuantity;

                        // Retrieve supplier price
                        $supplierProduct = SupplierProduct::where('product_id', $product->id)->first();
                        $supplierPrice = $supplierProduct ? $supplierProduct->price : 0;

                        // Calculate margin percentage
                        $margin = $product->default_selling_price > 0
                            ? (($product->default_selling_price - $supplierPrice) / $product->default_selling_price) * 100
                            : 0;

                        $productData = [
                            'id' => $product->id,
                            'name' => $product->name,
                            'sku' => $product->sku,
                            'barcode' => $product->barcode,
                            'description' => $product->description,
                            'model' => $product->model,
                            'product_category_id' => $product->product_category_id,
                            'product_category' => $product->productCategory ? $product->productCategory->name : null,
                            'product_unit_id' => $product->product_unit_id,
                            'product_unit' => $product->productUnit ? $product->productUnit->name : null,
                            'minimum_quantity' => $product->minimum_quantity,
                            'available_quantity' => $availableQuantity,
                            'quantity_level' => $availableQuantity < $product->minimum_quantity ? 'Below Minimum' : 'Above Minimum',
                            'default_selling_price' => $product->default_selling_price,
                            'supplier_price' => $supplierPrice,
                            'margin_percentage' => round($margin, 2) . '%',
                            'image_url' => $product->image_url,
                            'created_at' => $product->created_at,
                            'updated_at' => $product->updated_at,
                            'status_id' => $product->status,
                            'created_by' => $product->creator,
                            'updated_by' => $product->updater,
                            'location' => $product->location,
                            'warehouse' => $product->warehouse,
                        ];

                        if ($product->productGroup->productType->name == 'consumable') {
                            $productData['inventory_consumables'] = $product->inventoryConsumables
                                ->where('quantity', '>', 0)
                                ->map(function ($inventory) {
                                    $expiryDate = Carbon::parse($inventory->expiry_date);
                                    $remainingTime = now()->diff($expiryDate);

                                    // Determine status
                                    $status = 'VIABLE';
                                    $remainingTimeString = $remainingTime->y . ' Years, ' . $remainingTime->m . ' Months, and ' . $remainingTime->d . ' Days';
                                    
                                    if ($expiryDate->isPast()) {
                                        $status = 'EXPIRED';
                                        $expiredTime = $expiryDate->diff(now());
                                        $remainingTimeString = 'Expired ' . $expiredTime->y . ' Years, ' . $expiredTime->m . ' Months, and ' . $expiredTime->d . ' Days Ago';
                                    } elseif ($remainingTime->m < 1 && $remainingTime->y === 0) {
                                        $status = 'EXPIRING';
                                    }

                                    return [
                                        'id' => $inventory->id,
                                        'product_id' => $inventory->product_id,
                                        'purchase_order_item_id' => $inventory->purchase_order_item_id,
                                        'barcode' => $inventory->barcode,
                                        'lot_number' => $inventory->lot_number,
                                        'expiry_date' => $inventory->expiry_date,
                                        'quantity' => $inventory->quantity,
                                        'notes' => $inventory->notes,
                                        'created_by' => $inventory->created_by,
                                        'updated_by' => $inventory->updated_by,
                                        'created_at' => $inventory->created_at,
                                        'updated_at' => $inventory->updated_at,
                                        'remaining_time' => $remainingTimeString, // Meaningful time for expired items
                                        'status' => $status,
                                    ];
                                });
                        }

                        if ($product->productGroup->productType->name === 'equipment') {
                            $statusesToCheck = ['In Stock', 'Return Demo (working)', 'Available'];
                            $productData['inventory_equipment'] = $product->inventoryEquipment
                                ->filter(function ($equipment) use ($statusesToCheck) {
                                    return in_array($equipment->status->name, $statusesToCheck, true);
                                });
                        }

                        return $productData;
                    });

                    return [
                        'group_id' => $productGroup->id,
                        'group_name' => $productGroup->name,
                        'products' => $products
                    ];
                });

                return [
                    'type_id' => $productType->id,
                    'type_name' => $productType->name,
                    'product_groups' => $productGroups
                ];
            });

        return response()->json($productTypes);
    }*/


   

    public function getAllProducts($productId = null)  
    {  
        $query = Product::with([  
            'productUnit',  
            'supplier',  
            'location',  
            'warehouse',  
            'status',  
            'creator',  
            'updater',  
            'tags',  
            'incomingStocks.calibrationRecords',  
            'incomingStocks.maintenanceRecords'  
        ]);

        // If productId is provided, filter the query
        if ($productId !== null) {
            $query->where('id', $productId);
        }

        $products = $query->get()  
            ->map(function ($product) {  
                $availableQuantity = $product->incomingStocks  
                    ->filter(fn($stock) => is_null($stock->expiration_date) || !Carbon::parse($stock->expiration_date)->isPast())  
                    ->sum('quantity');  

                if ($product->incomingStocks->isNotEmpty()) {  
                    $groupedStocks = $product->incomingStocks  
                        ->groupBy(fn($stock) => implode('|', [  
                            $stock->purchase_order_item_id,  
                            $stock->serial_number ?? 'NULL',  
                            $stock->lot_number ?? 'NULL',  
                            $stock->expiration_date ?? 'NULL',  
                            $stock->product_id  
                        ]))  
                        ->map(function ($stocks) use ($product) {  
                            $firstStock = $stocks->first();  
                            $remainingTimeString = null;  
                            $status = 'In Stock';  
                            $quantity = 0;  

                            if (!$product->is_machine) {  
                                if (is_null($firstStock->lot_number) || is_null($firstStock->expiration_date)) {  
                                    $status = 'INSTOCK';  
                                    $quantity = $stocks->sum('quantity');  
                                } else {  
                                    $expirationDate = Carbon::parse($firstStock->expiration_date);  
                                    $today = Carbon::today();  
                                    $remainingTime = $today->diff($expirationDate);  

                                    if ($expirationDate->isPast()) {  
                                        $status = 'EXPIRED';  
                                        $remainingTimeString = 'Expired ' . $remainingTime->y . ' Years, ' . $remainingTime->m . ' Months, and ' . $remainingTime->d . ' Days Ago';  
                                    } elseif ($expirationDate->greaterThan($today->addMonths(3))) {  
                                        $status = 'VIABLE';  
                                        $remainingTimeString = 'Expires in ' . $remainingTime->y . ' Years, ' . $remainingTime->m . ' Months, and ' . $remainingTime->d . ' Days';  
                                    } else {  
                                        $status = 'EXPIRING';  
                                        $remainingTimeString = 'Expiring in ' . $remainingTime->m . ' Months and ' . $remainingTime->d . ' Days';  
                                    }  
                                    $quantity = $stocks->sum('quantity');  
                                }  
                            }  

                            // Retrieve and sort calibration and maintenance records by date (latest first)
                            $calibrationRecords = $firstStock->calibrationRecords
                                ->sortByDesc('calibration_date')
                                ->map(fn($record) => [
                                    'calibration_date' => $record->calibration_date,
                                    'calibrated_by' => $record->calibrated_by,
                                    'calibration_notes' => $record->calibration_notes,
                                    'calibration_status_id' => $record->calibration_status_id
                                ])->values()->toArray();

                            $maintenanceRecords = $firstStock->maintenanceRecords
                                ->sortByDesc('maintenance_date')
                                ->map(fn($record) => [
                                    'maintenance_date' => $record->maintenance_date,
                                    'next_maintenance_date' => $record->next_maintenance_date,
                                    'performed_by' => $record->performed_by,
                                    'description' => $record->description
                                ])->values()->toArray();

                            // Determine if calibration is needed
                            $latestCalibration = $calibrationRecords[0] ?? null;
                            $forCalibration = !$latestCalibration;

                            // Determine if maintenance is needed
                            $latestMaintenance = $maintenanceRecords[0] ?? null;
                            $nextMaintenanceDate = $latestMaintenance['next_maintenance_date'] ?? null;
                            $forMaintenance =  (Carbon::today())->diffInDays( Carbon::parse($nextMaintenanceDate)) < 30;

                            
                            return [  
                                'purchase_order_item_id' => $firstStock->purchase_order_item_id,  
                                'serial_number' => $firstStock->serial_number,  
                                'lot_number' => $firstStock->lot_number,  
                                'expiration_date' => $firstStock->expiration_date,  
                                'product_id' => $firstStock->product_id,  
                                'quantity' => $quantity,  
                                'status' => $status,  
                                'remaining_time' => $remainingTimeString,  
                                'barcodes' => $stocks->pluck('barcode')->toArray(),  
                                'calibration_records' => $calibrationRecords,  
                                'for_calibration' => (bool) $forCalibration,  
                                'maintenance_records' => $maintenanceRecords,  
                                'for_maintenance' => (bool) $forMaintenance  
                            ];  
                        })  
                        ->values();  
                } else {  
                    $groupedStocks = collect();  
                }  

                return array_merge($product->toArray(), [  
                    'available_quantity' => $availableQuantity,  
                    'quantity_level' => $availableQuantity == 0  
                    ? 'No Stock'  
                    : ($availableQuantity < $product->minimum_quantity ? 'Below Minimum' : 'Above Minimum'),  
                    'default_selling_price' => number_format($product->supplier_price + ($product->supplier_price * ($product->profit_margin / 100)), 2, '.', ''),  
                    'incoming_stocks' => $groupedStocks->toArray()  
                ]);  
            });  

            return response()->json($productId !== null ? $products->first() : $products);
    }

    /**
     * Display a listing of the products.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $products = Product::all();
        return response()->json($products);
    }

    /**
     * Store a newly created product in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'model' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'product_unit_id' => 'required|exists:product_units,id',
            'minimum_quantity' => 'required|integer|min:0',
            'profit_margin' => 'required|numeric|min:0|max:100',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Validate image upload
            'supplier_id' => 'nullable|exists:suppliers,id',
            'supplier_price' => 'required|numeric|min:0',
            'location_id' => 'nullable|exists:locations,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'is_machine' => 'required|boolean',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public'); // Save in storage/app/public/products
            $validatedData['image_url'] = asset("storage/{$imagePath}"); // Generate accessible URL
        }

        // Assign logged-in user
        $validatedData['created_by'] = auth()->id();
        $validatedData['updated_by'] = auth()->id();

        // Set default status_id
        $validatedData['status_id'] = 1;

        $product = Product::create($validatedData);

        return response()->json([
            'message' => 'Product successfully created',
            'product' => $product
        ], 201);
    }

    /**
     * Display the specified product.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        return response()->json($product);
    }

    /**
     * Update the specified product in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'model' => 'nullable|string|max:255',
            'description' => 'sometimes|required|string',
            'product_unit_id' => 'sometimes|required|exists:product_units,id',
            'minimum_quantity' => 'sometimes|required|integer|min:0',
            'profit_margin' => 'sometimes|required|numeric|min:0|max:100',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Image validation
            'supplier_id' => 'nullable|exists:suppliers,id',
            'supplier_price' => 'sometimes|required|numeric|min:0',
            'location_id' => 'nullable|exists:locations,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'is_machine' => 'sometimes|required|boolean', 
            'status_id' => 'sometimes|required|exists:statuses,id', 
        ]);

        // Handle image upload if a new image is provided
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public'); // Store image in storage/app/public/products
            $validatedData['image_url'] = asset("storage/{$imagePath}"); // Store accessible image URL
        }

        // Automatically set updated_by to the logged-in user
        $validatedData['updated_by'] = auth()->id();

        // Update the product record
        $product->update($validatedData);

        return response()->json([
            'message' => 'Product successfully updated',
            'product' => $product
        ]);
    }
    /**
     * Remove the specified product from storage.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json(null, 204);
    }
}
