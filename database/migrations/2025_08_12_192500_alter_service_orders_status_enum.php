<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Ajusta o ENUM de status para suportar os novos estados
        DB::statement("ALTER TABLE service_orders MODIFY COLUMN status ENUM('open','in_progress','in_service','warranty','service_finished','no_repair','finished','canceled') NOT NULL DEFAULT 'open'");
    }

    public function down(): void
    {
        // Reverte para o conjunto anterior mais restrito (caso necessário)
        DB::statement("ALTER TABLE service_orders MODIFY COLUMN status ENUM('open','in_progress','finished','canceled') NOT NULL DEFAULT 'open'");
    }
};


