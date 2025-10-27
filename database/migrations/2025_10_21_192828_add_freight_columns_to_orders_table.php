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
        Schema::table('orders', function (Blueprint $table) {
            $table->integer('freight_mode')->nullable()->after('status');
            $table->string('freight_payer', 50)->nullable()->after('freight_mode');
            $table->foreignId('carrier_id')->nullable()->constrained('carriers')->onDelete('set null')->after('freight_payer');
            $table->decimal('freight_cost', 10, 2)->nullable()->after('carrier_id');
            $table->text('freight_obs')->nullable()->after('freight_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['freight_mode', 'freight_payer', 'carrier_id', 'freight_cost', 'freight_obs']);
        });
    }
};
