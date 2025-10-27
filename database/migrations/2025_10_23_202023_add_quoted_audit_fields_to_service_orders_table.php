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
            if (!Schema::hasColumn('service_orders', 'quoted_by')) {
                $table->unsignedBigInteger('quoted_by')->nullable()->after('updated_by');
                $table->foreign('quoted_by')->references('id')->on('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('service_orders', 'quoted_at')) {
                $table->timestamp('quoted_at')->nullable()->after('quoted_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            if (Schema::hasColumn('service_orders', 'quoted_by')) {
                $table->dropForeign(['quoted_by']);
                $table->dropColumn('quoted_by');
            }
            if (Schema::hasColumn('service_orders', 'quoted_at')) {
                $table->dropColumn('quoted_at');
            }
        });
    }
};