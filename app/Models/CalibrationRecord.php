<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalibrationRecord extends Model
{
    //
    protected $fillable = [
        'inventory_equipment_id',
        'calibration_date',
        'next_calibration_due',
        'calibrated_by',
        'calibration_status_id',
        'calibration_notes',
        'created_by',
        'updated_by',
    ];

    public function inventoryEquipment()
    {
        return $this->belongsTo(InventoryEquipment::class);
    }

    public function calibrationStatus()
    {
        return $this->belongsTo(Status::class, 'calibration_status_id');
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
