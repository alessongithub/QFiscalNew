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
        // Inserir configurações padrão para OS
        $settings = [
            ['key' => 'os_max_installments', 'value' => '3', 'description' => 'Máximo de parcelas para OS'],
            ['key' => 'os_interest_rate', 'value' => '0.00', 'description' => 'Taxa de juros por parcela (%)'],
            ['key' => 'os_interest_type', 'value' => 'simple', 'description' => 'Tipo de juros (simple/compound)'],
            ['key' => 'os_payment_methods', 'value' => 'cash,card,pix,transfer,boleto,mixed', 'description' => 'Métodos de pagamento disponíveis para OS'],
        ];
        
        foreach ($settings as $setting) {
            \App\Models\Setting::updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'description' => $setting['description'],
                    'tenant_id' => null, // Configuração global
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remover configurações de OS
        \App\Models\Setting::whereIn('key', [
            'os_max_installments',
            'os_interest_rate', 
            'os_interest_type',
            'os_payment_methods'
        ])->delete();
    }
};
