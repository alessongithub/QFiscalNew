<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "Verificando usuários admin...\n";

$admin = User::where('is_admin', 1)->first();

if ($admin) {
    echo "Admin encontrado:\n";
    echo "Nome: " . $admin->name . "\n";
    echo "Email: " . $admin->email . "\n";
    echo "ID: " . $admin->id . "\n";
} else {
    echo "Nenhum usuário admin encontrado.\n";
    echo "Criando usuário admin padrão...\n";
    
    $admin = User::create([
        'name' => 'Administrador',
        'email' => 'admin@qfiscal.com.br',
        'password' => bcrypt('admin123'),
        'is_admin' => 1,
        'tenant_id' => 1
    ]);
    
    echo "Usuário admin criado:\n";
    echo "Email: admin@qfiscal.com.br\n";
    echo "Senha: admin123\n";
}
