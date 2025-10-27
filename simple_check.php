<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

$admin = User::where('is_admin', 1)->first();

if ($admin) {
    echo "ADMIN ENCONTRADO:\n";
    echo "Email: " . $admin->email . "\n";
    echo "Nome: " . $admin->name . "\n";
} else {
    echo "CRIANDO ADMIN PADRAO:\n";
    User::create([
        'name' => 'Administrador',
        'email' => 'admin@qfiscal.com.br',
        'password' => bcrypt('admin123'),
        'is_admin' => 1,
        'tenant_id' => 1
    ]);
    echo "Email: admin@qfiscal.com.br\n";
    echo "Senha: admin123\n";
}
