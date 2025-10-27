<?php

namespace Database\Seeders;

use App\Models\GatewayConfig;
use Illuminate\Database\Seeder;

class GatewayConfigSeeder extends Seeder
{
    public function run(): void
    {
        $config = GatewayConfig::query()->first();
        if (!$config) {
            $config = new GatewayConfig();
        }
        $config->fill([
            'provider' => 'mercadopago',
            'mode' => 'sandbox',
            'public_key_sandbox' => 'TEST-26d013d7-984f-4ca5-ba12-00699e984269',
            'access_token_sandbox' => 'TEST-3245630414117041-040109-ca5cd7127be9465ce7d9a06a35634cce-2365079108',
            'webhook_secret' => 'f2ce4cc691f9fd46c84208b593af9a14d55043264b1a54aa4aae34000ba5be41',
            'block_login_after_days' => 3,
        ]);
        $config->save();
    }
}


