<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('warranty_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_item_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('service_order_item_id')->nullable()->constrained()->onDelete('set null');
            $table->string('serial_number')->nullable();
            $table->date('warranty_start');
            $table->date('warranty_until');
            $table->enum('warranty_type', ['standard', 'extended', 'supplier'])->default('standard');
            $table->text('reason')->nullable();
            $table->foreignId('technician_id')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('recurrence_count')->default(1);
            $table->boolean('is_supplier_warranty')->default(false);
            $table->enum('supplier_status', ['awaiting_return', 'waiting_replacement', 'returned_to_customer'])->nullable();
            $table->timestamps();
            
            // Índices para performance
            $table->index('serial_number');
            $table->index('service_order_id');
            $table->index('warranty_until');
            $table->index('recurrence_count');
            $table->index(['tenant_id', 'warranty_until']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('warranty_history');
    }
};