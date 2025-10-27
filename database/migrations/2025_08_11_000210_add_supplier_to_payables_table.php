<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payables', function (Blueprint $table) {
            if (!Schema::hasColumn('payables', 'supplier_id')) {
                $table->unsignedBigInteger('supplier_id')->nullable()->after('tenant_id');
                $table->index('supplier_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payables', function (Blueprint $table) {
            if (Schema::hasColumn('payables', 'supplier_id')) {
                $table->dropIndex(['supplier_id']);
                $table->dropColumn('supplier_id');
            }
        });
    }
};


