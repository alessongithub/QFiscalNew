<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('service_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('number');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['open', 'in_progress', 'in_service', 'warranty', 'service_finished', 'no_repair', 'finished', 'canceled'])->default('open');
            $table->boolean('is_warranty')->default(false);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('discount_total', 10, 2)->default(0);
            $table->decimal('addition_total', 10, 2)->default(0);
            $table->integer('warranty_days')->default(0);
            $table->date('warranty_until')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->boolean('issue_nfse')->default(false);
            $table->timestamps();
            
            $table->unique(['tenant_id', 'number']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('service_orders');
    }
};
