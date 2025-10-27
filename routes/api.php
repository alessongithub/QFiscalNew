<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Models\NcmRule;
use App\Http\Controllers\Api\EmissorAuthController;
use App\Http\Controllers\Api\PolicyController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Desative o uso de sanctum para evitar erro quando não instalado/configurado
Route::middleware('auth')->get('/user', function (Request $request) {
    return $request->user();
});

// API para produtos (para busca dinâmica)
Route::middleware('auth')->get('/products', function (Request $request) {
    $query = \App\Models\Product::where('tenant_id', auth()->user()->tenant_id)
        ->where('active', 1);
    
    if ($request->has('search')) {
        $search = $request->get('search');
        $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }
    
    return $query->select('id', 'name', 'description', 'price', 'unit')
        ->orderBy('name')
        ->get();
});

// Endpoint para obter CFOP padrão por categoria (usado no auto-preenchimento no cadastro de produto)
Route::middleware('auth')->get('/categories/{category}/default-cfop', [CategoryController::class, 'defaultCfop']);

// Endpoint simples para checar se um NCM requer GTIN
Route::middleware('auth')->get('/ncm/{ncm}/requires-gtin', function (string $ncm) {
    $rule = NcmRule::where('ncm', $ncm)->first();
    return response()->json([
        'ncm' => $ncm,
        'requires_gtin' => (bool)($rule->requires_gtin ?? false),
        'note' => $rule->note ?? null,
    ]);
});

// Endpoint para buscar clientes (autocomplete)
Route::middleware('auth')->get('/clients/search', function (Request $request) {
    $q = $request->get('q', '');
    
    // Log para debug
    \Log::info('API Clients Search - Query: ' . $q . ' - User: ' . auth()->user()->id);
    
    if (strlen($q) < 3) {
        \Log::info('API Clients Search - Query too short: ' . strlen($q));
        return response()->json([]);
    }
    
    $tenantId = auth()->user()->tenant_id;
    \Log::info('API Clients Search - Tenant ID: ' . $tenantId);
    
    $clients = \App\Models\Client::where('tenant_id', $tenantId)
        ->where('name', 'like', "%{$q}%")
        ->orderBy('name')
        ->limit(10)
        ->get(['id', 'name']);
    
    \Log::info('API Clients Search - Found clients: ' . $clients->count());
    
    return response()->json($clients);
});

// Rotas do Emissor Delphi
Route::prefix('emissor')->group(function () {
    // Autenticação
    Route::post('/auth', [EmissorAuthController::class, 'authenticate']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/validate', [EmissorAuthController::class, 'validateToken']);
        Route::post('/auth/logout', [EmissorAuthController::class, 'logout']);
        // Política efetiva do tenant/usuário logado
        Route::get('/policy', [PolicyController::class, 'tenantPolicy']);
    });
});
