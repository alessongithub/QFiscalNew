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
        Schema::create('storage_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['data', 'files']);
            $table->integer('quantity_mb');
            $table->decimal('price', 10, 2);
            $table->enum('status', ['pending', 'active', 'cancelled'])->default('pending');
            $table->date('expires_at')->nullable();
            $table->timestamps();
            
            // Índices para performance
            $table->index(['tenant_id', 'status']);
            $table->index('type');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storage_addons');
    }
};
