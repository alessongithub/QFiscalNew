<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ative sua conta - QFiscal</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 2px solid #667eea;
        }
        .logo {
            max-width: 200px;
            height: auto;
        }
        .content {
            padding: 30px 0;
        }
        .button {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            padding: 20px 0;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
        .highlight {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ asset('logo/logo.png') }}" alt="QFiscal" class="logo">
        <h1 style="color: #667eea; margin: 10px 0;">QFiscal</h1>
    </div>

    <div class="content">
        <h2>Olá, {{ $user->name }}!</h2>
        
        <p>Seja bem-vindo ao QFiscal! Sua conta foi criada com sucesso para a empresa <strong>{{ $tenant->name }}</strong>.</p>
        
        <div class="highlight">
            <h3>📋 Dados da sua conta:</h3>
            <ul>
                <li><strong>Empresa:</strong> {{ $tenant->name }}</li>
                <li><strong>Email:</strong> {{ $user->email }}</li>
                <li><strong>Plano:</strong> {{ $tenant->plan->name ?? 'Gratuito' }}</li>
            </ul>
        </div>

        <p>Para começar a usar o sistema, você precisa ativar sua conta clicando no botão abaixo:</p>
        
        <div style="text-align: center;">
            <a href="{{ $activation_url }}" class="button">Ativar Minha Conta</a>
        </div>

        <p style="margin-top: 20px;">
            <strong>Importante:</strong> Este link é válido por 24 horas. Se não conseguir clicar no botão, copie e cole o link abaixo no seu navegador:
        </p>
        
        <p style="word-break: break-all; background: #f8f9fa; padding: 10px; border-radius: 3px;">
            {{ $activation_url }}
        </p>

        <p>Após ativar sua conta, você poderá fazer login em:</p>
        <p><a href="{{ $login_url }}" style="color: #667eea;">{{ $login_url }}</a></p>

        <div class="highlight">
            <h3>🚀 Próximos passos:</h3>
            <ol>
                <li>Ative sua conta clicando no botão acima</li>
                <li>Faça login no sistema</li>
                <li>Configure os dados da sua empresa</li>
                <li>Comece a usar o ERP e emissor fiscal!</li>
            </ol>
        </div>

        <p>Se você não solicitou esta conta, pode ignorar este email.</p>
    </div>

    <div class="footer">
        <p><strong>QFiscal - ERP e Emissor Fiscal</strong></p>
        <p>Este é um email automático, não responda a esta mensagem.</p>
        <p>Para suporte, entre em contato conosco através do sistema.</p>
    </div>
</body>
</html> 