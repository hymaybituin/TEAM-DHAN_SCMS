<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('calibration_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_equipment_id')->constrained('inventory_equipment');
            $table->date('calibration_date');
            $table->date('next_calibration_due')->nullable();
            $table->string('calibrated_by')->nullable();
            $table->foreignId('calibration_status_id')->constrained('statuses');
            $table->text('calibration_notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calibration_records');
    }
};
