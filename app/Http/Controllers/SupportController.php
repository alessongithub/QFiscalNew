<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SupportController extends Controller
{
    public function create()
    {
        return view('support.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'category' => 'required|string|in:technical,billing,feature,account,other',
            'priority' => 'required|string|in:low,medium,high,urgent',
            'message' => 'required|string|min:10',
            'phone' => 'nullable|string|max:20',
        ]);

        $tenant = auth()->user()->tenant;
        $user = auth()->user();

        try {
            Mail::raw($this->buildEmailContent($validated, $tenant, $user), function ($message) use ($validated, $tenant, $user) {
                $message->to('suporte@qfiscal.com.br')
                    ->subject("[Suporte QFiscal] {$validated['subject']} - Tenant #{$tenant->id}")
                    ->replyTo($user->email, $user->name ?? 'Usuário');
            });

            Log::info('Support ticket sent', [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'category' => $validated['category'],
                'priority' => $validated['priority'],
            ]);

            return redirect()->route('support.success');
        } catch (\Throwable $e) {
            Log::error('Failed to send support email', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenant->id,
            ]);

            return back()->withInput()->with('error', 'Erro ao enviar mensagem. Tente novamente ou entre em contato diretamente por email.');
        }
    }

    public function success()
    {
        return view('support.success');
    }

    private function buildEmailContent(array $validated, $tenant, $user): string
    {
        $categories = [
            'technical' => 'Suporte Técnico',
            'billing' => 'Faturamento/Assinatura',
            'feature' => 'Sugestão de Funcionalidade',
            'account' => 'Conta/Perfil',
            'other' => 'Outro',
        ];

        $priorities = [
            'low' => 'Baixa',
            'medium' => 'Média',
            'high' => 'Alta',
            'urgent' => 'Urgente',
        ];

        $content = "NOVO CHAMADO DE SUPORTE\n";
        $content .= "========================\n\n";
        $content .= "Tenant ID: {$tenant->id}\n";
        $content .= "Nome da Empresa: {$tenant->name}\n";
        $content .= "CNPJ: {$tenant->cnpj}\n";
        $content .= "Email da Empresa: {$tenant->email}\n\n";
        $content .= "Usuário: {$user->name} ({$user->email})\n";
        if (!empty($validated['phone'])) {
            $content .= "Telefone: {$validated['phone']}\n";
        }
        $content .= "\n";
        $content .= "Categoria: {$categories[$validated['category']]}\n";
        $content .= "Prioridade: {$priorities[$validated['priority']]}\n";
        $content .= "Assunto: {$validated['subject']}\n\n";
        $content .= "Mensagem:\n";
        $content .= str_repeat('-', 50) . "\n";
        $content .= $validated['message'] . "\n";
        $content .= str_repeat('-', 50) . "\n\n";
        $content .= "Data/Hora: " . now()->format('d/m/Y H:i:s') . "\n";

        return $content;
    }
}

