<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('sku')->nullable();
            $table->string('ean')->nullable();
            $table->string('unit', 6);
            $table->string('ncm', 8)->nullable();
            $table->string('cest', 7)->nullable();
            $table->string('cfop', 4)->nullable();
            $table->string('origin', 2)->nullable();
            $table->string('csosn', 3)->nullable();
            $table->string('cst_icms', 3)->nullable();
            $table->string('cst_pis', 2)->nullable();
            $table->string('cst_cofins', 2)->nullable();
            $table->decimal('aliquota_icms', 5, 2)->nullable();
            $table->decimal('aliquota_pis', 5, 2)->nullable();
            $table->decimal('aliquota_cofins', 5, 2)->nullable();
            $table->decimal('price', 10, 2);
            $table->enum('type', ['product', 'service'])->default('product');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
};
