<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configurações de Email
    |--------------------------------------------------------------------------
    |
    | Copie este arquivo para .env e preencha com suas configurações
    |
    */

    'MAIL_MAILER' => 'smtp',
    'MAIL_HOST' => 'smtp.gmail.com', // ou seu servidor SMTP
    'MAIL_PORT' => 587,
    'MAIL_USERNAME' => 'seu-email@gmail.com',
    'MAIL_PASSWORD' => 'sua-senha-de-app', // Senha de app do Google
    'MAIL_ENCRYPTION' => 'tls',
    'MAIL_FROM_ADDRESS' => 'seu-email@gmail.com',
    'MAIL_FROM_NAME' => 'QFiscal',

    /*
    |--------------------------------------------------------------------------
    | Instruções para Gmail
    |--------------------------------------------------------------------------
    |
    | 1. Ative a verificação em duas etapas no Google
    | 2. Gere uma senha de app em:
    |    - Configurações da Conta Google
    |    - Segurança
    |    - Verificação em duas etapas
    |    - Senhas de app
    | 3. Use essa senha no MAIL_PASSWORD
    |
    */
];