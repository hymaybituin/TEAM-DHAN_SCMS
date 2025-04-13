<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'sku',  // Ensure SKU is fillable
        'barcode',  // Ensure barcode is fillable
        'product_group_id',
        'product_category_id',  // Added product_category_id for category association
        'product_unit_id',  // Replaced unit_of_measure with product_unit_id
        'model', // Added model
        'description',
        'minimum_quantity',
        'profit_margin',
        'image_url',
        'location_id',
        'warehouse_id',
        'status_id',
        'created_by',
        'updated_by'
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
    
        static::creating(function ($product) {
            // Generate SKU: First 4 letters of product name + 4 random alphanumeric characters
            $product->sku = strtoupper(substr($product->name, 0, 4)) . strtoupper(Str::random(4));
    
            // Generate Barcode: First 3 letters of SKU
            $product->barcode = strtoupper(substr($product->sku, 0, 3));
        });
    
        static::created(function ($product) {
            // Ensure Barcode is unique
            while (self::where('barcode', $product->barcode)->exists()) {
                $product->barcode = strtoupper(substr($product->sku, 0, 3)) . strtoupper(Str::random(3));
            }
    
            // Save the updated Barcode
            $product->save();
        });
    }


    /**
     * Get the user who created the product.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who updated the product.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the purchase order items for the product.
     */
    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * Get the inventory consumables for the product.
     */
    public function inventoryConsumables()
    {
        return $this->hasMany(InventoryConsumable::class);
    }

    /**
     * Get the inventory equipment for the product.
     */
    public function inventoryEquipment()
    {
        return $this->hasMany(InventoryEquipment::class);
    }

    public function incomingStocks()
    {
        return $this->hasMany(IncomingStock::class, 'product_id');
    }

    /**
     * Get the product group.
     */
    public function productGroup()
    {
        return $this->belongsTo(ProductGroup::class, 'product_group_id');
    }

    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function productUnit()
    {
        return $this->belongsTo(ProductUnit::class, 'product_unit_id');
    }

    /**
     * Get the status.
     */
    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
