<?php
require_once 'vendor/autoload.php';
 = require_once 'bootstrap/app.php';
->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use Illuminate\\Support\\Facades\\Schema;
use Illuminate\\Database\\Schema\\Blueprint;
use App\\Models\\User;

try {
    if (Schema::hasTable('users')) {
        if (!Schema::hasColumn('users','is_admin')) {
            Schema::table('users', function (Blueprint ) {
                ->boolean('is_admin')->default(false);
            });
            echo "Added column is_admin\n";
        }
        if (!Schema::hasColumn('users','tenant_id')) {
            Schema::table('users', function (Blueprint ) {
                ->unsignedBigInteger('tenant_id')->nullable();
            });
            echo "Added column tenant_id\n";
        }
    } else {
        echo "Tabela users não existe.\n";
    }

     = User::where('email','admin@qfiscal.com.br')->first();
    if (!) {
         = User::create([
            'name' => 'Administrador',
            'email' => 'admin@qfiscal.com.br',
            'password' => 'admin123',
            'tenant_id' => 1,
            'is_admin' => 1,
        ]);
        echo "Admin criado.\n";
    } else {
        ->update(['is_admin' => 1, 'tenant_id' => ->tenant_id ?: 1]);
        echo "Admin já existia; atualizado.\n";
    }
    echo "OK\n";
} catch (Throwable ) {
    echo "Erro: ".->getMessage()."\n";
}
