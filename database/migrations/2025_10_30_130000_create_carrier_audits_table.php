<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carrier_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('carrier_id')->nullable()->constrained('carriers')->onDelete('set null');
            $table->string('action'); // created, updated, deleted
            $table->json('changes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['tenant_id']);
            $table->index(['carrier_id']);
            $table->index(['action']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrier_audits');
    }
};


