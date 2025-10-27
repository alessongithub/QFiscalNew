<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('receivable_id')->nullable()->constrained()->onDelete('set null');
            $table->string('number');
            $table->date('issue_date');
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->string('notes', 255)->nullable();
            $table->enum('status', ['issued', 'canceled'])->default('issued');
            $table->timestamps();
            
            $table->unique(['tenant_id', 'number']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('receipts');
    }
};
