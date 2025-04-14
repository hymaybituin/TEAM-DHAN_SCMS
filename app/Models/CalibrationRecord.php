<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalibrationRecord extends Model
{
    //
    protected $fillable = [
        'incoming_stock_id',
        'calibration_date',
        'next_calibration_date',
        'calibrated_by',
        'calibration_status_id',
        'calibration_notes',
        'created_by',
        'updated_by',
    ];

    public function incomingStock()
    {
        return $this->belongsTo(IncomingStock::class);
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
