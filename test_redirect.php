<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;

echo "=== TESTANDO REDIRECIONAMENTO ===\n";

// Simular login do admin
if (Auth::attempt(['email' => 'admin@qfiscal.com.br', 'password' => 'admin123'])) {
    $user = Auth::user();
    echo "✅ Login bem-sucedido!\n";
    echo "Usuário: " . $user->name . "\n";
    echo "is_admin: " . ($user->is_admin ? 'SIM' : 'NÃO') . "\n";
    
    // Testar a lógica de redirecionamento
    if ($user->is_admin) {
        echo "🔀 REDIRECIONAMENTO: /admin/dashboard\n";
        echo "URL: " . route('admin.dashboard') . "\n";
    } else {
        echo "🔀 REDIRECIONAMENTO: /dashboard\n";
        echo "URL: " . route('dashboard') . "\n";
    }
    
    Auth::logout();
} else {
    echo "❌ Login falhou!\n";
}
