<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ResetPasswordController extends Controller
{
    public function showResetForm()
    {
        return view('admin.reset-password');
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'new_password' => 'required|min:6|confirmed',
        ], [
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Digite um e-mail válido.',
            'email.exists' => 'Este e-mail não está cadastrado.',
            'new_password.required' => 'A nova senha é obrigatória.',
            'new_password.min' => 'A senha deve ter pelo menos 6 caracteres.',
            'new_password.confirmed' => 'As senhas não coincidem.',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'Usuário não encontrado.']);
        }

        // Atualizar a senha
        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return redirect()->route('login')
            ->with('success', 'Senha redefinida com sucesso! Você pode fazer login agora.');
    }
}
