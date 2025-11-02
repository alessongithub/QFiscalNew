<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_withdrawal_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('cash_withdrawal_id')->nullable()->constrained('cash_withdrawals')->onDelete('set null');
            $table->string('action'); // created, updated, deleted, reversed
            $table->json('changes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['tenant_id']);
            $table->index(['cash_withdrawal_id']);
            $table->index(['action']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_withdrawal_audits');
    }
};


