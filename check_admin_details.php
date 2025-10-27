<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Tenant;

echo "=== VERIFICANDO DETALHES DO ADMIN ===\n";

$admin = User::where('is_admin', 1)->first();

if ($admin) {
    echo "Admin encontrado:\n";
    echo "ID: " . $admin->id . "\n";
    echo "Nome: " . $admin->name . "\n";
    echo "Email: " . $admin->email . "\n";
    echo "is_admin: " . ($admin->is_admin ? 'SIM' : 'NÃO') . "\n";
    echo "tenant_id: " . $admin->tenant_id . "\n";
    echo "created_at: " . $admin->created_at . "\n";
    echo "updated_at: " . $admin->updated_at . "\n";
    
    // Verificar se o tenant existe
    $tenant = Tenant::find($admin->tenant_id);
    if ($tenant) {
        echo "\nTenant encontrado:\n";
        echo "ID: " . $tenant->id . "\n";
        echo "Nome: " . $tenant->name . "\n";
        echo "Status: " . $tenant->status . "\n";
        echo "Active: " . ($tenant->active ? 'SIM' : 'NÃO') . "\n";
    } else {
        echo "\nERRO: Tenant não encontrado para o admin!\n";
    }
    
    // Testar autenticação
    echo "\n=== TESTANDO AUTENTICAÇÃO ===\n";
    if (Auth::attempt(['email' => $admin->email, 'password' => 'admin123'])) {
        echo "✅ Autenticação com 'admin123' funcionou!\n";
        Auth::logout();
    } else {
        echo "❌ Autenticação com 'admin123' falhou!\n";
    }
    
} else {
    echo "Nenhum admin encontrado!\n";
}

echo "\n=== VERIFICANDO TODOS OS USUÁRIOS ===\n";
$users = User::all();
foreach ($users as $user) {
    echo "ID: {$user->id} | Nome: {$user->name} | Email: {$user->email} | Admin: " . ($user->is_admin ? 'SIM' : 'NÃO') . " | Tenant: {$user->tenant_id}\n";
}
