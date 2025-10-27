<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('inbound_invoices')) {
            Schema::create('inbound_invoices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('supplier_id')->nullable();
                $table->string('access_key', 60)->nullable();
                $table->string('number', 20)->nullable();
                $table->string('series', 10)->nullable();
                $table->date('issue_date')->nullable();
                $table->decimal('total_products', 12, 2)->nullable();
                $table->decimal('total_invoice', 12, 2)->nullable();
                $table->json('raw_summary')->nullable();
                $table->timestamps();

                $table->index(['tenant_id','supplier_id']);
            });
        }

        if (!Schema::hasTable('inbound_invoice_items')) {
            Schema::create('inbound_invoice_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('inbound_invoice_id');
                $table->string('product_code', 100)->nullable();
                $table->string('product_name', 255);
                $table->string('ean', 20)->nullable();
                $table->string('ncm', 10)->nullable();
                $table->string('cfop', 10)->nullable();
                $table->string('unit', 10)->nullable();
                $table->decimal('quantity', 12, 3)->default(0);
                $table->decimal('unit_price', 12, 4)->default(0);
                $table->decimal('total_price', 12, 2)->default(0);
                $table->timestamps();

                $table->index('inbound_invoice_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inbound_invoice_items');
        Schema::dropIfExists('inbound_invoices');
    }
};


