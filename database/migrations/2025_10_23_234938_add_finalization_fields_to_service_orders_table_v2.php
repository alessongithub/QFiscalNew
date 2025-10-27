<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            // Verificar se os campos já existem antes de adicionar
            if (!Schema::hasColumn('service_orders', 'finalization_notes')) {
                $table->text('finalization_notes')->nullable()->after('warranty_until');
            }
            if (!Schema::hasColumn('service_orders', 'delivery_method')) {
                $table->enum('delivery_method', ['pickup', 'delivery', 'shipping'])->nullable()->after('finalization_notes');
            }
            if (!Schema::hasColumn('service_orders', 'delivered_by')) {
                $table->foreignId('delivered_by')->nullable()->constrained('users')->onDelete('set null')->after('delivery_method');
            }
            if (!Schema::hasColumn('service_orders', 'client_signature')) {
                $table->text('client_signature')->nullable()->after('delivered_by');
            }
            if (!Schema::hasColumn('service_orders', 'equipment_condition')) {
                $table->enum('equipment_condition', ['perfect', 'good', 'damaged'])->nullable()->after('client_signature');
            }
            if (!Schema::hasColumn('service_orders', 'accessories_included')) {
                $table->text('accessories_included')->nullable()->after('equipment_condition');
            }
            if (!Schema::hasColumn('service_orders', 'final_amount')) {
                $table->decimal('final_amount', 10, 2)->nullable()->after('accessories_included');
            }
            if (!Schema::hasColumn('service_orders', 'payment_method')) {
                $table->enum('payment_method', ['cash', 'card', 'pix', 'transfer'])->nullable()->after('final_amount');
            }
            if (!Schema::hasColumn('service_orders', 'payment_received')) {
                $table->boolean('payment_received')->default(false)->after('payment_method');
            }
            if (!Schema::hasColumn('service_orders', 'finalized_by')) {
                $table->foreignId('finalized_by')->nullable()->constrained('users')->onDelete('set null')->after('payment_received');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            if (Schema::hasColumn('service_orders', 'delivered_by')) {
                $table->dropForeign(['delivered_by']);
            }
            if (Schema::hasColumn('service_orders', 'finalized_by')) {
                $table->dropForeign(['finalized_by']);
            }
            
            $columns = [
                'finalization_notes',
                'delivery_method',
                'delivered_by',
                'client_signature',
                'equipment_condition',
                'accessories_included',
                'final_amount',
                'payment_method',
                'payment_received',
                'finalized_by'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('service_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};