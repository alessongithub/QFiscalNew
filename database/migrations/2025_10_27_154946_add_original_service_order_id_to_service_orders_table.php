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
        Schema::table('service_orders', function (Blueprint $table) {
            $table->foreignId('original_service_order_id')->nullable()->constrained('service_orders')->onDelete('cascade')->after('id');
            $table->index('original_service_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropForeign(['original_service_order_id']);
            $table->dropColumn('original_service_order_id');
        });
    }
};
