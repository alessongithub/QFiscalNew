<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Plan;

echo "=== VERIFICANDO PLANOS EXISTENTES ===\n";

$plans = Plan::all();

if ($plans->count() > 0) {
    foreach ($plans as $plan) {
        echo "ID: {$plan->id}\n";
        echo "Nome: {$plan->name}\n";
        echo "Slug: {$plan->slug}\n";
        echo "Preço: R$ {$plan->price}\n";
        echo "Ativo: " . ($plan->active ? 'SIM' : 'NÃO') . "\n";
        echo "---\n";
    }
} else {
    echo "Nenhum plano encontrado!\n";
}
