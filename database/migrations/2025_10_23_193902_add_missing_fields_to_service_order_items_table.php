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
        Schema::table('service_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('service_order_items', 'name')) {
                $table->string('name')->after('product_id');
            }
            if (!Schema::hasColumn('service_order_items', 'discount_value')) {
                $table->decimal('discount_value', 10, 2)->default(0)->after('unit_price');
            }
            if (!Schema::hasColumn('service_order_items', 'line_total')) {
                $table->decimal('line_total', 10, 2)->after('discount_value');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('service_order_items', 'name')) {
                $table->dropColumn('name');
            }
            if (Schema::hasColumn('service_order_items', 'discount_value')) {
                $table->dropColumn('discount_value');
            }
            if (Schema::hasColumn('service_order_items', 'line_total')) {
                $table->dropColumn('line_total');
            }
        });
    }
};