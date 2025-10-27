<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inbound_invoice_items')) {
            Schema::table('inbound_invoice_items', function (Blueprint $table) {
                if (!Schema::hasColumn('inbound_invoice_items', 'linked_product_id')) {
                    $table->unsignedBigInteger('linked_product_id')->nullable()->after('total_price');
                }
                if (!Schema::hasColumn('inbound_invoice_items', 'linked_movement_id')) {
                    $table->unsignedBigInteger('linked_movement_id')->nullable()->after('linked_product_id');
                }
                if (!Schema::hasColumn('inbound_invoice_items', 'linked_at')) {
                    $table->timestamp('linked_at')->nullable()->after('linked_movement_id');
                }
                $table->index(['linked_product_id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('inbound_invoice_items')) {
            Schema::table('inbound_invoice_items', function (Blueprint $table) {
                if (Schema::hasColumn('inbound_invoice_items', 'linked_at')) {
                    $table->dropColumn('linked_at');
                }
                if (Schema::hasColumn('inbound_invoice_items', 'linked_movement_id')) {
                    $table->dropColumn('linked_movement_id');
                }
                if (Schema::hasColumn('inbound_invoice_items', 'linked_product_id')) {
                    $table->dropColumn('linked_product_id');
                }
            });
        }
    }
};

?>


