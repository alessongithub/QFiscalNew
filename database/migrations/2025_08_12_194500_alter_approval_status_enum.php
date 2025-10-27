<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Garantir valores permitidos e default para approval_status
        // Atualiza nulos existentes para 'awaiting'
        DB::statement("UPDATE service_orders SET approval_status='awaiting' WHERE approval_status IS NULL OR approval_status=''");
        // Altera ENUM para incluir 'awaiting' como default e não nulo
        DB::statement("ALTER TABLE service_orders MODIFY COLUMN approval_status ENUM('awaiting','approved','customer_notified','not_approved') NOT NULL DEFAULT 'awaiting'");
    }

    public function down(): void
    {
        // Reverte (mantém como NOT NULL mas sem 'awaiting')
        DB::statement("ALTER TABLE service_orders MODIFY COLUMN approval_status ENUM('approved','customer_notified','not_approved') NULL");
    }
};


