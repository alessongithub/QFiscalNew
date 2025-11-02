<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipt_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('receipt_id')->nullable()->constrained('receipts')->onDelete('set null');
            $table->string('action'); // created, updated, canceled
            $table->json('changes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['tenant_id']);
            $table->index(['receipt_id']);
            $table->index(['action']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_audits');
    }
};


