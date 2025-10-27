<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                // alterar status para inteiro pequeno, se atualmente não for inteiro
                try {
                    $table->unsignedTinyInteger('status')->default(0)->change();
                } catch (\Throwable $e) {
                    // fallback: adicionar nova coluna e migrar dados
                    if (!Schema::hasColumn('invoices', 'status_int')) {
                        $table->unsignedTinyInteger('status_int')->default(0)->after('due_date');
                    }
                }
            });
        }
    }

    public function down(): void
    {
        // não reverte para evitar perda de dados; opcionalmente poderia voltar para string
    }
};


