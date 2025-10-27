<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inbound_invoice_items') && !Schema::hasColumn('inbound_invoice_items', 'link_locked')) {
            Schema::table('inbound_invoice_items', function (Blueprint $table) {
                $table->boolean('link_locked')->default(false)->after('linked_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('inbound_invoice_items') && Schema::hasColumn('inbound_invoice_items', 'link_locked')) {
            Schema::table('inbound_invoice_items', function (Blueprint $table) {
                $table->dropColumn('link_locked');
            });
        }
    }
};

?>


