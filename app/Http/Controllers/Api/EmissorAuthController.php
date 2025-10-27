<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class EmissorAuthController extends Controller
{
    public function authenticate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 400);
        }

        // Buscar o tenant pelo email do usuário
        $tenant = Tenant::whereHas('users', function ($query) use ($request) {
            $query->where('email', $request->email);
        })->first();

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant não encontrado'
            ], 404);
        }

        // Verificar se o tenant tem acesso ao emissor
        $plan = $tenant->plan;
        if (!$plan || !($plan->features['has_emissor'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => 'Seu plano não inclui acesso ao emissor fiscal'
            ], 403);
        }

        // Verificar se o plano não expirou
        if ($tenant->plan_expires_at && $tenant->plan_expires_at->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Seu plano expirou. Renove para continuar usando o emissor'
            ], 403);
        }

        // Verificar a senha do usuário
        $user = $tenant->users()->where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email ou senha incorretos'
            ], 401);
        }

        // Gerar token de acesso
        $token = $user->createToken('emissor-delphi')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Autenticação realizada com sucesso',
            'data' => [
                'token' => $token,
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'plan' => [
                    'name' => $plan->name,
                    'features' => $plan->features
                ],
                'expires_at' => $tenant->plan_expires_at?->toISOString()
            ]
        ]);
    }

    public function validateToken(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido'
            ], 401);
        }

        $tenant = $user->tenant;
        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant não encontrado'
            ], 404);
        }

        $plan = $tenant->plan;
        if (!$plan || !($plan->features['has_emissor'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => 'Seu plano não inclui acesso ao emissor fiscal'
            ], 403);
        }

        if ($tenant->plan_expires_at && $tenant->plan_expires_at->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Seu plano expirou'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Token válido',
            'data' => [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'plan' => [
                    'name' => $plan->name,
                    'features' => $plan->features
                ]
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->tokens()->where('name', 'emissor-delphi')->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logout realizado com sucesso'
        ]);
    }
}
