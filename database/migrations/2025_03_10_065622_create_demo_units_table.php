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
        Schema::create('demo_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incoming_stock_id')->constrained('incoming_stocks');// Assuming incoming_id refers to another table
            $table->foreignId('company_id')->constrained('companies'); // Assuming company_id refers to companies table
            $table->dateTime('demo_start');
            $table->dateTime('demo_end')->nullable();
            $table->string('assigned_person');
            $table->string('status');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demo_units');
    }
};
