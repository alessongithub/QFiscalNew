<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;

echo "=== TESTANDO LOGIN DO ADMIN ===\n";

$admin = User::where('is_admin', 1)->first();

if ($admin) {
    echo "Admin encontrado:\n";
    echo "Email: " . $admin->email . "\n";
    echo "is_admin: " . ($admin->is_admin ? 'SIM' : 'NÃO') . "\n";
    
    // Testar autenticação
    if (Auth::attempt(['email' => $admin->email, 'password' => 'admin123'])) {
        echo "✅ Login bem-sucedido!\n";
        
        $user = Auth::user();
        echo "Usuário logado: " . $user->name . "\n";
        echo "is_admin: " . ($user->is_admin ? 'SIM' : 'NÃO') . "\n";
        
        // Simular o redirecionamento
        if ($user->is_admin) {
            echo "🔀 Deveria redirecionar para: /admin/dashboard\n";
        } else {
            echo "🔀 Deveria redirecionar para: /dashboard\n";
        }
        
        Auth::logout();
    } else {
        echo "❌ Login falhou!\n";
    }
} else {
    echo "Nenhum admin encontrado!\n";
}
