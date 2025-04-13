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


    public function getProductsWithQuantityAndStatus()
    {
        $productTypes = ProductType::with([
            'productGroups.products.incomingStocks',
            'productGroups.products.purchaseOrderItems.purchaseOrder.supplier'
        ])
        ->get()
        ->map(function ($productType) {
            $productGroups = $productType->productGroups->map(function ($productGroup) {
                $products = $productGroup->products->map(function ($product) {
                    // Determine if the product is equipment or consumable
                    $isEquipment = strtolower($product->productGroup->productType->name) === 'equipment';
    
                    // Adjust available stock calculation
                    $availableStocks = $isEquipment 
                        ? $product->incomingStocks // Keep all stocks for equipment 
                        : $product->incomingStocks->filter(function ($stock) {
                            return Carbon::parse($stock->expiration_date)->isFuture();
                        });
    
                    // Calculate total available quantity
                    $availableQuantity = $availableStocks->sum('quantity');
    
                    // Retrieve profit margin from the product level
                    $profitMargin = $product->profit_margin ?? 0;
    
                    $productData = [
                        'id' => $product->id,
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'barcode' => $product->barcode,
                        'description' => $product->description,
                        'model' => $product->model,
                        'minimum_quantity' => $product->minimum_quantity,
                        'available_quantity' => $availableQuantity,
                        'profit_margin' => round($profitMargin, 2) . '%',
                        'quantity_level' => $availableQuantity < $product->minimum_quantity ? 'Below Minimum' : 'Above Minimum',
                        'image_url' => $product->image_url,
                        'product_category_id' => $product->product_category_id,
                        'product_category' => $product->productCategory ? $product->productCategory->name : null,
                        'created_at' => $product->created_at,
                        'updated_at' => $product->updated_at,
                        'status_id' => $product->status,
                        'created_by' => $product->creator,
                        'updated_by' => $product->updater,
                        'location' => $product->location,
                        'warehouse' => $product->warehouse,
                        
                    ];
    
                    // **Group inventory items by purchase order number (`ponumber`)**
                    $inventoryItems = $product->incomingStocks->groupBy(function ($stock) {
                        return $stock->purchaseOrderItem->purchaseOrder->ponumber;
                    })->map(function ($groupedStocks, $ponumber) use ($profitMargin, $isEquipment) {
                        // Retrieve supplier details directly from purchase_orders table
                        $purchaseOrder = PurchaseOrder::where('ponumber', $ponumber)->first();
                        $supplierName = $purchaseOrder?->supplier->name ?? 'Unknown';
    
                        // Retrieve the supplier price from purchase order items
                        $supplierPrice = PurchaseOrderItem::where('purchase_order_id', $purchaseOrder?->id)->value('unit_price') ?? 0;
    
                        // Calculate selling price using the profit margin from the product level
                        $sellingPrice = $supplierPrice + ($supplierPrice * $profitMargin / 100);
    
                        return [
                            'supplier' => $supplierName,
                            'supplier_price' => round($supplierPrice, 2),
                            'profit_margin' => round($profitMargin, 2) . '%',
                            'selling_price' => round($sellingPrice, 2),
                            'items' => $groupedStocks->groupBy(function ($stock) use ($isEquipment) {
                                return $isEquipment 
                                    ? $stock->serial_number // Equipment grouped by serial number 
                                    : "{$stock->lot_number}-{$stock->expiration_date}";
                            })->map(function ($stocks) use ($isEquipment) {
                                return $isEquipment 
                                    ? [
                                        'serial_number' => $stocks->first()->serial_number,
                                        'total_quantity' => $stocks->sum('quantity')
                                    ]
                                    : [
                                        'lot_number' => $stocks->first()->lot_number,
                                        'expiration_date' => $stocks->first()->expiration_date,
                                        'total_quantity' => $stocks->sum('quantity'),
                                        'status' => Carbon::parse($stocks->first()->expiration_date)->isPast() ? 'expired' :
                                            (now()->diffInMonths(Carbon::parse($stocks->first()->expiration_date)) < 1 ? 'expiring' : 'viable')
                                    ];
                            })->values()
                        ];
                    });
    
                    $productData['inventory_items'] = $inventoryItems;
    
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
            'product_group_id' => 'required|exists:product_groups,id',
            'description' => 'required|string',
            'unit_of_measure' => 'required|string|max:255',
            'minimum_quantity' => 'required|numeric|min:0',
            'default_selling_price' => 'required|numeric|min:0',
            'created_by' => 'required|exists:users,id',
            'updated_by' => 'required|exists:users,id',
        ]);

        $product = Product::create($validatedData);

        return response()->json($product, 201);
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
            'product_group_id' => 'sometimes|required|exists:product_groups,id',
            'description' => 'sometimes|required|string',
            'unit_of_measure' => 'sometimes|required|string|max:255',
            'minimum_quantity' => 'sometimes|required|numeric|min:0',
            'default_selling_price' => 'sometimes|required|numeric|min:0',
            'created_by' => 'sometimes|required|exists:users,id',
            'updated_by' => 'sometimes|required|exists:users,id',
        ]);

        $product->update($validatedData);

        return response()->json($product);
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
