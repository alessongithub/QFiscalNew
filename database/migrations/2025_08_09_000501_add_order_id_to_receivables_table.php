<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('receivables', function (Blueprint $table) {
            if (!Schema::hasColumn('receivables', 'order_id')) {
                $table->foreignId('order_id')->nullable()->constrained('orders')->cascadeOnDelete()->after('client_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('receivables', function (Blueprint $table) {
            if (Schema::hasColumn('receivables', 'order_id')) {
                $table->dropConstrainedForeignId('order_id');
            }
        });
    }
};


