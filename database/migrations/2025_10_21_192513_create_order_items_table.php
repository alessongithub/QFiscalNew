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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('quantity', 12, 3);
            $table->string('unit', 10);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('discount_value', 10, 2)->default(0);
            $table->decimal('addition_value', 10, 2)->default(0);
            $table->decimal('line_total', 10, 2);
            $table->timestamps();
            
            $table->index(['tenant_id']);
            $table->index(['order_id']);
            $table->index(['product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
