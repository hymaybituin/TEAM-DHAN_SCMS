<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Tag;
use League\Csv\Reader;
use App\Models\Product;
use App\Models\Location;
use App\Models\Supplier;
use App\Models\ProductTag;
use App\Models\ProductUnit;
use App\Models\IncomingStock;
use App\Models\PurchaseOrder;
use Illuminate\Console\Command;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\DB;
use App\Models\PurchaseOrderStatus;
use App\Models\PurchaseOrderItemDelivery;

class ImportCSVCommand extends Command
{
    protected $signature = 'import:csv {file}';
    protected $description = 'Import products from a CSV file and generate purchase orders';

    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File not found: $filePath");
            return;
        }

        // Define status mappings
        $statusMap = [
            'Pending' => 1,
            'Approved' => 2,
            'Received' => 8,
            'Delivered' => 11,
        ];

        // Read CSV file
        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);

        // Group products by supplier
        $productsBySupplier = [];

        foreach ($csv as $record) {
            // Clean up spaces in product name and model
            $name = preg_replace('/\s+/', ' ', trim($record['product_name']));
            $model = preg_replace('/\s+/', ' ', trim($record['model']));

            // Get supplier ID
            $supplierId = $this->getSupplierId($record['suppliers']);
            if (!$supplierId) {
                $this->error("Supplier not found for: " . $record['suppliers']);
                continue;
            }

            // Check if product already exists
            $existingProduct = Product::where('name', $name)->where('model', $model)->first();

            if (!$existingProduct) {
                // Create product only if it does not exist
                $existingProduct = Product::create([
                    'name' => $name,
                    'model' => $model,
                    'minimum_quantity' => $record['minimum_qty'],
                    'profit_margin' => $record['profit_margin % (optional)'] ?? 0,
                    'supplier_price' => $record['supplier_price'],
                    'is_machine' => strtolower(trim($record['is_machine (Y/N)'])) === 'y' ? true : false,
                    'product_unit_id' => $this->getProductUnitId($record['product_unit']),
                    'supplier_id' => $supplierId,
                    'location_id' => $this->getLocationId($record['location']),
                    'warehouse_id' => 1,
                    'status_id' => 1,
                    'created_by' => 1,
                    'updated_by' => 1,
                ]);

                foreach (explode(',', $record['tags']) as $tagName) {
                    $tag = Tag::firstOrCreate(['name' => trim($tagName)]);
                    ProductTag::create([
                        'product_id' => $existingProduct->id,
                        'tag_id' => $tag->id,
                    ]);
                }
            }

            // Store relevant purchase order data including CSV values
            $productsBySupplier[$supplierId][] = [
                'product_id' => $existingProduct->id,
                'quantity' => intval($record['quantity']),
                'unit_price' => floatval($record['supplier_price']),
                'total_price' => intval($record['quantity']) * floatval($record['supplier_price']),
                'serial_number' => trim($record['serial_number']) ?: null,
                'lot_number' => trim($record['lot_number']) ?: null,
                'expiration_date' => $this->parseExpirationDate($record['date_expired']),
                     ];
        }

        DB::transaction(function () use ($productsBySupplier, $statusMap) {
            foreach ($productsBySupplier as $supplierId => $products) {
                // Create purchase order
                $purchaseOrder = PurchaseOrder::create([
                    'ponumber' => null,
                    'supplier_id' => $supplierId,
                    'order_date' => now(),
                    'total_amount' => 0,
                    'status_id' => $statusMap['Pending'],
                    'created_by' => 1,
                    'updated_by' => 1,
                ]);

                // Generate PO number with prefix
                $purchaseOrder->ponumber = 'I-' . 'P' . str_pad($purchaseOrder->id, 6, '0', STR_PAD_LEFT);
                $purchaseOrder->save();

                // Save initial PO status
                PurchaseOrderStatus::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'status_id' => $statusMap['Pending'],
                    'status_date' => now(),
                    'comments' => 'Purchase order created.',
                    'created_by' => 1,
                    'updated_by' => 1,
                ]);

                // Consolidate purchase order items
                foreach ($products as $productData) {
                    $poItemRecord = PurchaseOrderItem::create([
                        'purchase_order_id' => $purchaseOrder->id,
                        'product_id' => $productData['product_id'],
                        'quantity' => $productData['quantity'],
                        'unit_price' => $productData['unit_price'],
                        'total_price' => $productData['total_price'],
                        'created_by' => 1,
                        'updated_by' => 1,
                    ]);

                    // Save delivery record
                    PurchaseOrderItemDelivery::create([
                        'purchase_order_item_id' => $poItemRecord->id,
                        'delivered_quantity' => $productData['quantity'],
                        'delivery_date' => now(),
                        'created_by' => 1,
                        'updated_by' => 1,
                    ]);

                    // Save each unit individually in `incoming_stocks`
                    for ($i = 0; $i < $productData['quantity']; $i++) {
                        IncomingStock::create([
                            'purchase_order_item_id' => $poItemRecord->id,
                            'serial_number' => $productData['serial_number'],
                            'lot_number' => $productData['lot_number'],
                            'expiration_date' => $productData['expiration_date'],
                            'product_id' => $productData['product_id'],
                            'barcode' => now()->format('Ymd') . '-' . str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT),
                            'quantity' => 1,
                            'created_by_user_id' => 1,
                            'updated_by_user_id' => 1,
                        ]);
                    }
                }

                // Update PO status transitions
                foreach (['Approved', 'Received', 'Delivered'] as $status) {
                    PurchaseOrderStatus::create([
                        'purchase_order_id' => $purchaseOrder->id,
                        'status_id' => $statusMap[$status],
                        'status_date' => now(),
                        'comments' => "Status changed to $status.",
                        'created_by' => 1,
                        'updated_by' => 1,
                    ]);
                }

                // Set final status as "Delivered"
                $purchaseOrder->status_id = $statusMap['Delivered'];
                $purchaseOrder->save();
            }
        });

        $this->info('CSV Import and Purchase Orders created successfully!');
    }


    private function getProductUnitId($unitName)
    {
        return ProductUnit::firstOrCreate(
            ['name' => $unitName]
        )->id;
    }

    private function getSupplierId($supplierName)
    {
        return Supplier::firstOrCreate(
            ['name' => $supplierName],
            ['contact_info' => "", 'created_by' => 1, 'updated_by' => 1]
        )->id;
    }

    private function getLocationId($locationName)
    {
        return Location::firstOrCreate(
            ['name' => $locationName],
            ['created_by' => 1, 'updated_by' => 1]
        )->id;
    }
    private function parseExpirationDate($date)
    {
        try {
            return Carbon::parse(trim($date))->format('Y-m-d');
        } catch (\Exception $e) {
            return '1990-01-01'; // Default to January 1, 1990 if parsing fails
        }
    }
}