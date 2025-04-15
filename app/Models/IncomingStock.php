<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncomingStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_item_id',
        'serial_number',
        'lot_number',
        'expiration_date',
        'product_id',
        'barcode',
        'quantity',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    // ✅ Relationships
    public function purchaseOrderItem()
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedByUser()
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function maintenanceRecords()
    {
        return $this->hasMany(MaintenanceRecord::class, 'incoming_stock_id');
    }

    public function calibrationRecords()
    {
        return $this->hasMany(CalibrationRecord::class, 'incoming_stock_id');
    }

    // ✅ Scope for Barcode Filtering
    public function scopeByBarcodes($query, array $barcodes)
    {
        return $query->whereIn('barcode', $barcodes);
    }

    // ✅ Bulk Update Function (Update by Barcode)
    public static function updateByBarcodes(array $barcodes, array $data)
    {
        return self::whereIn('barcode', $barcodes)->update($data);
    }
}