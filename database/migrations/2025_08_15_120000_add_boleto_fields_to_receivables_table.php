<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('receivables', function (Blueprint $table) {
            $table->string('boleto_mp_id')->nullable()->after('document_number');
            $table->string('boleto_url')->nullable()->after('boleto_mp_id');
            $table->string('boleto_pdf_url')->nullable()->after('boleto_url');
            $table->string('boleto_barcode')->nullable()->after('boleto_pdf_url');
            $table->timestamp('boleto_emitted_at')->nullable()->after('boleto_barcode');
        });
    }

    public function down(): void
    {
        Schema::table('receivables', function (Blueprint $table) {
            $table->dropColumn(['boleto_mp_id','boleto_url','boleto_pdf_url','boleto_barcode','boleto_emitted_at']);
        });
    }
};


