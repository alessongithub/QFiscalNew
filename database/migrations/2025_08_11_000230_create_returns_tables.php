<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('returns')) {
            Schema::create('returns', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('order_id');
                $table->decimal('total_refund', 12, 2)->default(0);
                $table->string('refund_method', 50)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['tenant_id','order_id']);
            });
        }

        if (!Schema::hasTable('return_items')) {
            Schema::create('return_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('return_id');
                $table->unsignedBigInteger('order_item_id');
                $table->decimal('quantity', 12, 3)->default(0);
                $table->decimal('unit_price', 12, 4)->default(0);
                $table->decimal('line_total', 12, 2)->default(0);
                $table->timestamps();

                $table->index(['return_id','order_item_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('return_items');
        Schema::dropIfExists('returns');
    }
};


