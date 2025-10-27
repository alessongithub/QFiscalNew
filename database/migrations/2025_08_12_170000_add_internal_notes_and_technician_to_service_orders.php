<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('service_orders')) {
            Schema::table('service_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('service_orders', 'internal_notes')) {
                    $table->text('internal_notes')->nullable()->after('status');
                }
                if (!Schema::hasColumn('service_orders', 'technician_user_id')) {
                    // Add column first, then foreign key
                    $table->unsignedBigInteger('technician_user_id')->nullable()->after('finalized_at');
                }
            });
            // Add FK separately to avoid issues when batching
            if (!Schema::hasColumn('service_orders', 'technician_user_id')) {
                // nothing to do
            } else {
                // Try to add foreign key; ignore if it already exists
                try {
                    Schema::table('service_orders', function (Blueprint $table) {
                        $table->foreign('technician_user_id', 'fk_so_technician_user')
                              ->references('id')->on('users')
                              ->nullOnDelete();
                    });
                } catch (\Throwable $e) {
                    // safe to ignore if FK exists or storage engine limitations
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('service_orders')) {
            try {
                Schema::table('service_orders', function (Blueprint $table) {
                    if (Schema::hasColumn('service_orders', 'technician_user_id')) {
                        try { $table->dropForeign('fk_so_technician_user'); } catch (\Throwable $e) {}
                        $table->dropColumn('technician_user_id');
                    }
                    if (Schema::hasColumn('service_orders', 'internal_notes')) {
                        $table->dropColumn('internal_notes');
                    }
                });
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }
};


