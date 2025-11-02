<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('entity_type'); // receivable | payable
            $table->unsignedBigInteger('entity_id');
            $table->string('action'); // created | updated | paid | canceled | reversed | bulk_paid
            $table->text('notes')->nullable();
            $table->json('changes')->nullable();
            $table->timestamps();
            $table->index(['tenant_id']);
            $table->index(['entity_type','entity_id']);
            $table->index(['user_id']);
            $table->index(['action']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_audits');
    }
};


