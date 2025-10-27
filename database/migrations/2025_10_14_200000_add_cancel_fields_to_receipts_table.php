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
        Schema::table('receipts', function (Blueprint $table) {
            $table->timestamp('canceled_at')->nullable()->after('status');
            $table->string('canceled_by')->nullable()->after('canceled_at');
            $table->text('cancel_reason')->nullable()->after('canceled_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropColumn(['canceled_at', 'canceled_by', 'cancel_reason']);
        });
    }
};
