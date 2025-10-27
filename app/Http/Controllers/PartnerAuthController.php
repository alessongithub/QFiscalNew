<?php

namespace App\Http\Controllers;

use App\Models\PartnerUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class PartnerAuthController extends Controller
{
    public function showSetPassword(Request $request)
    {
        $token = $request->query('token');
        if (!$token) { abort(404); }
        $user = PartnerUser::where('invite_token', $token)->first();
        if (!$user) { abort(404); }
        return view('public.partners.set-password', compact('token','user'));
    }

    public function setPassword(Request $request)
    {
        $data = $request->validate([
            'token' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);
        $user = PartnerUser::where('invite_token', $data['token'])->firstOrFail();
        $user->password = Hash::make($data['password']);
        $user->invite_token = null;
        $user->save();
        return redirect()->route('partner.login')->with('success','Senha definida. Faça login.');
    }

    public function showLogin()
    {
        return view('public.partners.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        if (Auth::guard('partner')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->route('partner.dashboard');
        }
        return back()->withErrors(['email' => 'Credenciais inválidas'])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::guard('partner')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('partner.login');
    }

    public function showPasswordForm()
    {
        return view('public.partners.password');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);
        $user = Auth::guard('partner')->user();
        if (!$user || !Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Senha atual incorreta']);
        }
        $user->password = Hash::make($data['password']);
        $user->save();
        return back()->with('success','Senha atualizada com sucesso');
    }
}


