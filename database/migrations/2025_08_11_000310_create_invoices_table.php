<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
                $table->decimal('amount', 12, 2);
                $table->date('due_date')->nullable();
                $table->string('status')->default('open'); // open, paid, canceled
                $table->string('description')->nullable();
                // External gateway linkage
                $table->string('external_preference_id')->nullable();
                $table->string('external_payment_id')->nullable();
                $table->string('external_status')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};


