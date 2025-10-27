<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('service_order_warranty_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_order_id')->constrained()->onDelete('cascade');
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->integer('warranty_days_old')->nullable();
            $table->integer('warranty_days_new')->nullable();
            $table->text('reason');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            // Índices para auditoria
            $table->index(['service_order_id', 'created_at']);
            $table->index('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('service_order_warranty_logs');
    }
};