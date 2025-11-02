<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ncm_rule_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ncm_rule_id')->constrained('ncm_rules')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action'); // created, updated, deleted
            $table->json('changes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['ncm_rule_id']);
            $table->index(['user_id']);
            $table->index(['action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ncm_rule_audits');
    }
};


