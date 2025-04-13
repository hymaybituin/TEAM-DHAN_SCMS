<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceRecord extends Model
{
    //
    protected $fillable = [
        'inventory_equipment_id',
        'maintenance_date',
        'description',
        'performed_by',
        'created_by',
        'updated_by',
    ];

    public function inventoryEquipment()
    {
        return $this->belongsTo(InventoryEquipment::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
