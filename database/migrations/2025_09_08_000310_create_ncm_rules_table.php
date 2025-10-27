<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ncm_rules', function (Blueprint $table) {
            $table->id();
            $table->string('ncm', 20)->index();
            $table->boolean('requires_gtin')->default(false);
            $table->string('note', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ncm_rules');
    }
};

 

