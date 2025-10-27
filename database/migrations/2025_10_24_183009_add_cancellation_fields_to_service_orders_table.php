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
            if (!Schema::hasColumn('service_orders', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable();
            }
            if (!Schema::hasColumn('service_orders', 'cancelled_by')) {
                $table->unsignedBigInteger('cancelled_by')->nullable();
                $table->foreign('cancelled_by')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            if (Schema::hasColumn('service_orders', 'cancelled_by')) {
                $table->dropForeign(['cancelled_by']);
                $table->dropColumn('cancelled_by');
            }
            if (Schema::hasColumn('service_orders', 'cancelled_at')) {
                $table->dropColumn('cancelled_at');
            }
        });
    }
};