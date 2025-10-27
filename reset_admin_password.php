<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "=== REDEFININDO SENHA DO ADMIN ===\n";

$admin = User::where('is_admin', 1)->first();

if ($admin) {
    echo "Admin encontrado:\n";
    echo "Email: " . $admin->email . "\n";
    echo "Nome: " . $admin->name . "\n";
    
    // Nova senha
    $newPassword = 'admin123';
    
    // Atualizar senha
    $admin->update([
        'password' => Hash::make($newPassword)
    ]);
    
    echo "\nSenha redefinida com sucesso!\n";
    echo "Nova senha: " . $newPassword . "\n";
    echo "Email: " . $admin->email . "\n";
} else {
    echo "Nenhum admin encontrado. Criando novo admin...\n";
    
    $admin = User::create([
        'name' => 'Administrador',
        'email' => 'admin@qfiscal.com.br',
        'password' => Hash::make('admin123'),
        'is_admin' => 1,
        'tenant_id' => 1
    ]);
    
    echo "Admin criado:\n";
    echo "Email: admin@qfiscal.com.br\n";
    echo "Senha: admin123\n";
}

echo "\n=== CREDENCIAIS PARA LOGIN ===\n";
echo "URL: http://localhost:8000/login\n";
echo "Email: admin@qfiscal.com.br\n";
echo "Senha: admin123\n";
