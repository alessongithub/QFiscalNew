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
            if (!Schema::hasColumn('service_order_items', 'line_total')) {
                $table->decimal('line_total', 10, 2)->after('total_price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('service_order_items', 'line_total')) {
                $table->dropColumn('line_total');
            }
        });
    }
};