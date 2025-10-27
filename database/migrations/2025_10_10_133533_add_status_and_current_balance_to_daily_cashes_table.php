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
        Schema::table('daily_cashes', function (Blueprint $table) {
            $table->enum('status', ['open', 'closed'])->default('open')->after('date');
            $table->decimal('current_balance', 10, 2)->default(0)->after('net_total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_cashes', function (Blueprint $table) {
            $table->dropColumn(['status', 'current_balance']);
        });
    }
};